<?php

namespace App\Http\Controllers;

use App\Models\ZatcaDevice;
use App\Services\Zatca\ZatcaApiClient;
use App\Services\Zatca\ZatcaComplianceTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ZatcaComplianceController extends Controller
{
    public function __construct(private ZatcaComplianceTestService $tests) {}

    private function authorizeDevice(ZatcaDevice $device): void
    {
        abort_unless(auth()->check() && (int) $device->company_id === (int) auth()->user()->company_id, 403);
    }

    private function loadRun(ZatcaDevice $device): ?array
    {
        $row = DB::table('zatca_compliance_runs')->where('zatca_device_id', $device->id)->first();
        if (!$row || !hash_equals($this->tests->fingerprint($device), $row->fingerprint)) {
            return null;
        }
        return ['supplier' => json_decode($row->supplier, true, 512, JSON_THROW_ON_ERROR),
            'tests' => json_decode($row->tests, true, 512, JSON_THROW_ON_ERROR)];
    }

    private function saveRun(ZatcaDevice $device, array $run): void
    {
        DB::table('zatca_compliance_runs')->updateOrInsert(
            ['zatca_device_id' => $device->id],
            ['fingerprint' => $this->tests->fingerprint($device),
                'supplier' => json_encode($run['supplier'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'tests' => json_encode($run['tests'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function action(ZatcaDevice $device, callable $callback)
    {
        $this->authorizeDevice($device);
        $lock = Cache::lock('zatca-compliance:'.$device->id, 180);
        if (!$lock->get()) {
            return back()->with('error', 'هناك طلب جارٍ لهذا الجهاز. انتظر انتهاءه.');
        }
        try {
            $device->refresh();
            $this->authorizeDevice($device);
            $this->tests->assertReady($device);
            return $callback($device);
        } catch (Throwable $e) {
            // Do not expose API credentials, signed XML or a raw exception trace.
            $message = get_class($e) === RuntimeException::class
                ? $e->getMessage() : 'تعذر إكمال العملية. راجع الاتصال وإعدادات خدمات التوقيع ثم أعد المحاولة.';
            return back()->with('error', $message);
        } finally {
            $lock->release();
        }
    }

    public function show(ZatcaDevice $device)
    {
        $this->authorizeDevice($device);
        $run = null; $definitions = []; $problem = null; $ready = false;
        try {
            $definitions = $this->tests->definitions($device);
            $this->tests->assertReady($device);
            $run = $this->loadRun($device);
            $ready = $run !== null && $this->tests->allPassed($device, $run['tests']);
        } catch (Throwable $e) {
            $problem = get_class($e) === RuntimeException::class ? $e->getMessage() : 'تعذر قراءة شهادة الجهاز.';
        }
        return view('zatca.devices.compliance-tests', compact('device', 'run', 'definitions', 'problem', 'ready'));
    }

    public function prepare(Request $request, ZatcaDevice $device)
    {
        $this->authorizeDevice($device);
        $address = $request->validate([
            'registration_number' => ['required', 'string', 'regex:/\A[0-9]{10}\z/'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['required', 'string', 'regex:/\A[0-9]{4}\z/'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'regex:/\A[0-9]{5}\z/'],
        ]);
        return $this->action($device, function ($device) use ($address) {
            $supplier = $address + ['name' => (string) $device->organization_name,
                'vat_number' => (string) $device->organization_identifier,
                'registration_scheme' => 'CRN', 'country_code' => 'SA'];
            if (!preg_match('/\A3[0-9]{13}3\z/', $supplier['vat_number']) || trim($supplier['name']) === '') {
                throw new RuntimeException('أكمل الاسم والرقم الضريبي الصحيحين في بيانات الجهاز.');
            }
            $this->saveRun($device, ['supplier' => $supplier, 'tests' => $this->tests->fixtures($device, $supplier)]);
            return back()->with('success', 'تم تجهيز المستندات. ابدأ بالاختبار الأول.');
        });
    }

    public function run(ZatcaDevice $device, string $test)
    {
        return $this->action($device, function ($device) use ($test) {
            $run = $this->loadRun($device);
            $definitions = $this->tests->definitions($device);
            if (!$run || !isset($definitions[$test], $run['tests'][$test])) {
                throw new RuntimeException('جهز مستندات الاختبار أولاً، أو أعد تجهيزها بعد تغيير الشهادة.');
            }
            if ($run['tests'][$test]['state'] === 'passed') {
                return back()->with('success', 'هذا الاختبار ناجح بالفعل.');
            }
            $previous = base64_encode(hash('sha256', '0'));
            foreach (array_keys($definitions) as $key) {
                if ($key === $test) { break; }
                if (($run['tests'][$key]['state'] ?? '') !== 'passed') {
                    throw new RuntimeException('أكمل الاختبارات بالترتيب الظاهر.');
                }
                $previous = $run['tests'][$key]['signed']['invoice_hash'];
            }
            $entry = $run['tests'][$test];
            $entry['data']['previous_invoice_hash'] = $previous;
            // Retain the exact UUID/XML across retries, including timeouts.
            if (!isset($entry['signed'])) {
                $entry['signed'] = $this->tests->sign($device, $entry['data']);
            }
            $run['tests'][$test] = $entry;
            $this->saveRun($device, $run);
            try {
                $signed = $entry['signed'];
                $response = app(ZatcaApiClient::class)->checkInvoiceCompliance(
                    $device, $signed['uuid'], $signed['invoice_hash'], $signed['signed_xml']
                );
                $entry['state'] = $this->tests->passed($response) ? 'passed' : 'failed';
                $entry['http_status'] = $response['status'] ?? null;
                $entry['messages'] = [];
                foreach (['errorMessages', 'warningMessages'] as $group) {
                    foreach ((array) data_get($response, 'body.validationResults.'.$group, []) as $message) {
                        if (is_array($message)) {
                            $entry['messages'][] = trim((string) ($message['code'] ?? '').' '.(string) ($message['message'] ?? ''));
                        }
                    }
                }
                if ($entry['state'] === 'failed' && !$entry['messages']) {
                    $entry['messages'][] = 'لم تصل نتيجة امتثال ناجحة. تحقق من بيانات اعتماد هذه البيئة وحالة HTTP.';
                }
            } catch (Throwable) {
                $entry['state'] = 'unknown';
                $entry['messages'] = ['لم تصل استجابة مؤكدة. إعادة المحاولة تستخدم المستند نفسه.'];
            }
            $entry['checked_at'] = now()->toIso8601String();
            // A credentials change during the HTTP call invalidates this run.
            $fingerprint = $this->tests->fingerprint($device);
            $device->refresh();
            if (!hash_equals($fingerprint, $this->tests->fingerprint($device))) {
                throw new RuntimeException('تغيرت بيانات الجهاز أثناء الاختبار؛ أعد تجهيز الاختبارات.');
            }
            $run['tests'][$test] = $entry;
            $this->saveRun($device, $run);
            return back()->with($entry['state'] === 'passed' ? 'success' : 'error',
                $entry['state'] === 'passed' ? 'نجح الاختبار. انتقل للاختبار التالي.' : 'لم ينجح الاختبار. التفاصيل موجودة أسفل اسمه.');
        });
    }

    public function production(ZatcaDevice $device)
    {
        return $this->action($device, function ($device) {
            if ($device->environment === 'developer') {
                throw new RuntimeException('طلب شهادة الإرسال من هذه الصفحة متاح للمحاكاة والإنتاج فقط.');
            }
            $run = $this->loadRun($device);
            if (!$run || !$this->tests->allPassed($device, $run['tests'])) {
                throw new RuntimeException('يجب نجاح جميع اختبارات الشهادة الحالية أولاً.');
            }
            $fingerprint = $this->tests->fingerprint($device);
            $response = app(ZatcaApiClient::class)->requestProductionCsid($device);
            $body = $response['body'] ?? [];
            if (!($response['successful'] ?? false) || !is_array($body) ||
                ($body['dispositionMessage'] ?? '') !== 'ISSUED' ||
                empty($body['binarySecurityToken']) || empty($body['secret']) || empty($body['requestID'])) {
                throw new RuntimeException('لم تُصدر شهادة الإرسال. HTTP '.($response['status'] ?? 'غير معروف').'. راجع اكتمال اختبارات هذه الشهادة.');
            }
            $candidate = clone $device;
            $candidate->status = 'production';
            $candidate->production_certificate = $body['binarySecurityToken'];
            $this->tests->assertKeyMatches($candidate);
            DB::transaction(function () use ($device, $body, $fingerprint) {
                $locked = ZatcaDevice::query()->lockForUpdate()->findOrFail($device->id);
                if ($locked->status !== 'compliance' || !hash_equals($fingerprint, $this->tests->fingerprint($locked))) {
                    throw new RuntimeException('تغيرت بيانات الجهاز أثناء إصدار الشهادة؛ لم يتم استبدال البيانات.');
                }
                $locked->production_certificate = $body['binarySecurityToken'];
                $locked->production_secret = $body['secret'];
                $locked->production_request_id = (string) $body['requestID'];
                $locked->status = 'production';
                $locked->auto_submit = false;
                $locked->save();
            });
            return redirect()->route('zatca.devices.index')->with('success',
                'تم الحصول على شهادة الإرسال. الإرسال التلقائي متوقف حتى تفعيله من صفحة الأجهزة.');
        });
    }
}

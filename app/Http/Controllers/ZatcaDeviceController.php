<?php

namespace App\Http\Controllers;

use App\Models\ZatcaDevice;
use App\Services\Zatca\ZatcaCsrService;
use App\Services\Zatca\ZatcaApiClient;
use App\Services\Zatca\ZatcaCertificateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class ZatcaDeviceController extends Controller
{
    public function index()
    {
        $devices = ZatcaDevice::query()
            ->where('company_id', auth()->user()->company_id)
            ->orderByDesc('id')
            ->get();

        return view('zatca.devices.index', compact('devices'));
    }

    public function create()
    {
        return view('zatca.devices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_name' => [
                'required',
                'string',
                'max:255',
            ],

            'egs_serial_number' => [
                'required',
                'string',
                'max:255',
            ],

            'common_name' => [
                'required',
                'string',
                'max:255',
            ],

            'organization_identifier' => [
                'required',
                'string',
                'regex:/^3\d{13}3$/',
            ],

            'organization_unit_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'organization_name' => [
                'required',
                'string',
                'max:255',
            ],

            'invoice_type' => [
                'required',
                'regex:/^[01]{4}$/',
                Rule::notIn(['0000']),
            ],

            'registered_address' => [
                'required',
                'string',
                'max:255',
            ],

            'business_category' => [
                'required',
                'string',
                'max:255',
            ],

            'branch_id' => [
                'nullable',
                'integer',
            ],
        ]);

        $device = ZatcaDevice::create([
            ...$data,

            'company_id' => auth()->user()->company_id,

            'environment' => config(
                'services.zatca.environment',
                'developer'
            ),

            'country_name' => 'SA',

            'status' => 'new',

            'created_by' => auth()->id(),
        ]);

        app(\App\Services\AuditLogService::class)->log(
            action: 'create',
            module: 'ZATCA Device',
            description: 'تم إنشاء وحدة فوترة إلكترونية لـ ZATCA: '
                . $device->device_name,
            model: $device,
            newValues: [
                'device_name' => $device->device_name,
                'egs_serial_number' => $device->egs_serial_number,
                'environment' => $device->environment,
                'organization_identifier' => $device->organization_identifier,
                'invoice_type' => $device->invoice_type,
                'status' => $device->status,
            ],
            companyId: $device->company_id,
            branchId: $device->branch_id
        );

        return redirect()
            ->route('zatca.devices.index')
            ->with(
                'success',
                'تم إنشاء جهاز ZATCA بنجاح.'
            );
    }

    public function generateCsr(
        ZatcaDevice $device,
        ZatcaCsrService $csrService
    ) {
        abort_unless(
            (int) $device->company_id
                === (int) auth()->user()->company_id,
            403
        );

        if ($device->status !== 'new') {
            return back()->with(
                'error',
                'تم إنشاء CSR لهذا الجهاز مسبقًا.'
            );
        }

        try {

            $device = $csrService->generate($device);

            app(\App\Services\AuditLogService::class)->log(
                action: 'generate_csr',
                module: 'ZATCA Device',
                description: 'تم إنشاء CSR لوحدة ZATCA: '
                    . $device->device_name,
                model: $device,
                oldValues: [
                    'status' => 'new',
                ],
                newValues: [
                    'status' => $device->status,
                    'environment' => $device->environment,
                    'egs_serial_number' => $device->egs_serial_number,
                ],
                companyId: $device->company_id,
                branchId: $device->branch_id
            );

            return back()->with(
                'success',
                'تم إنشاء Private Key و CSR بنجاح.'
            );

        } catch (Throwable $e) {

            report($e);

            return back()->with(
                'error',
                'فشل إنشاء CSR: ' . $e->getMessage()
            );
        }
    }


    public function requestCompliance(
        Request $request,
        ZatcaDevice $device,
        ZatcaApiClient $zatcaApi
    ) {
        abort_unless(
            (int) $device->company_id
                === (int) auth()->user()->company_id,
            403
        );

        if (! $device->csr) {
            return back()->with(
                'error',
                'يجب إنشاء CSR أولًا.'
            );
        }

        if (
            $device->compliance_certificate
            && $device->compliance_secret
        ) {
            return back()->with(
                'error',
                'تم الحصول على Compliance CSID لهذا الجهاز مسبقًا.'
            );
        }

        try {

            if ($device->environment !== config('services.zatca.environment')) {
                return back()->with(
                    'error',
                    'بيئة الجهاز لا تطابق بيئة الاتصال المحددة في الإعدادات.'
                );
            }

            $validator = \Illuminate\Support\Facades\Validator::make(
                $request->only('otp'),
                [
                    'otp' => ['required', 'string', 'regex:/\A[0-9]{6}\z/'],
                ],
                [
                    'otp.required' => 'أدخل رمز OTP الصادر من منصة فاتورة.',
                    'otp.string' => 'رمز OTP غير صحيح.',
                    'otp.regex' => 'رمز OTP يجب أن يتكون من 6 أرقام إنجليزية.',
                ]
            );

            // إزالة الرمز من بيانات الطلب بعد التحقق، وعدم إعادته عبر old input.
            $request->request->remove('otp');

            if ($validator->fails()) {
                return back()->with(
                    'error',
                    $validator->errors()->first('otp')
                );
            }

            $otp = $validator->validated()['otp'];
            unset($validator);



            $result = $zatcaApi->requestComplianceCsid(
                $device,
                $otp
            );

            if (
                empty($result['binarySecurityToken'])
                || empty($result['secret'])
                || empty($result['requestID'])
            ) {
                throw new \Exception(
                    'استجابة ZATCA لا تحتوي بيانات Compliance CSID المطلوبة.'
                );
            }

            $device->update([
                'compliance_certificate' =>
                    $result['binarySecurityToken'],

                'compliance_secret' =>
                    $result['secret'],

                'compliance_request_id' =>
                    (string) $result['requestID'],

                'status' => 'compliance',
            ]);

            app(\App\Services\AuditLogService::class)->log(
                action: 'compliance_csid',
                module: 'ZATCA Device',
                description:
                    'تم الحصول على Compliance CSID لجهاز ZATCA: '
                    . $device->device_name,

                model: $device,

                oldValues: [
                    'status' => 'csr_generated',
                ],

                newValues: [
                    'status' => 'compliance',
                    'request_id' =>
                        $device->compliance_request_id,
                ],

                companyId: $device->company_id,
                branchId: $device->branch_id
            );

            return back()->with(
                'success',
                'تم الحصول على Compliance CSID من ZATCA بنجاح.'
            );

        } catch (\Throwable $e) {

            report($e);

            return back()->with(
                'error',
                $e->getMessage()
            );
        }
    }

    public function toggleAutoSubmit(
        Request $request,
        ZatcaDevice $device,
        ZatcaCertificateService $certificateService
    ) {
        abort_unless(
            (int) $device->company_id
                === (int) auth()->user()->company_id,
            403
        );

        $data = $request->validate([
            'enabled' => [
                'required',
                'boolean',
            ],
        ]);

        $enabled = (bool) $data['enabled'];

        /*
        |--------------------------------------------------------------------------
        | التعطيل مسموح دائماً
        |--------------------------------------------------------------------------
        */
        if (! $enabled) {
            $device->update([
                'auto_submit' => false,
            ]);

            return back()->with(
                'success',
                'تم إيقاف الإرسال التلقائي إلى زاتكا.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | منع التفعيل في Developer Portal
        |--------------------------------------------------------------------------
        */
        $environment = config(
            'services.zatca.environment',
            'developer'
        );

        if (! in_array(
            $environment,
            ['simulation', 'production'],
            true
        )) {
            return back()->with(
                'error',
                'لا يمكن تفعيل الإرسال التلقائي في Developer Portal. يلزم إعداد Simulation أو Production.'
            );
        }

        if ($device->status !== 'production') {
            return back()->with(
                'error',
                'يجب الحصول على Production CSID قبل تفعيل الإرسال.'
            );
        }

        if (
            empty($device->production_certificate)
            || empty($device->production_secret)
            || empty($device->private_key)
        ) {
            return back()->with(
                'error',
                'بيانات شهادة Production أو المفتاح الخاص غير مكتملة.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | التأكد أن شهادة Production تطابق المفتاح الخاص
        |--------------------------------------------------------------------------
        */
        try {
            $certificateDetails =
                openssl_pkey_get_details(
                    $certificateService->publicKey(
                        $device
                    )
                );

            $privateKey =
                openssl_pkey_get_private(
                    $device->private_key
                );

            $privateDetails =
                $privateKey
                    ? openssl_pkey_get_details(
                        $privateKey
                    )
                    : false;

            $certificateX = data_get(
                $certificateDetails,
                'ec.x'
            );

            $certificateY = data_get(
                $certificateDetails,
                'ec.y'
            );

            $privateX = data_get(
                $privateDetails,
                'ec.x'
            );

            $privateY = data_get(
                $privateDetails,
                'ec.y'
            );

            $certificateMatches =
                is_string($certificateX)
                && is_string($certificateY)
                && is_string($privateX)
                && is_string($privateY)
                && hash_equals(
                    $certificateX,
                    $privateX
                )
                && hash_equals(
                    $certificateY,
                    $privateY
                );

            if (! $certificateMatches) {
                return back()->with(
                    'error',
                    'شهادة Production لا تطابق المفتاح الخاص، لذلك لم يتم تفعيل الإرسال.'
                );
            }
        } catch (Throwable $exception) {
            return back()->with(
                'error',
                'تعذر التحقق من شهادة ZATCA: '
                    . $exception->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | جهاز واحد فقط يكون مفعلاً
        |--------------------------------------------------------------------------
        */
        ZatcaDevice::query()
            ->where('company_id', $device->company_id)
            ->whereKeyNot($device->id)
            ->update([
                'auto_submit' => false,
            ]);

        $device->update([
            'auto_submit' => true,
        ]);

        app(\App\Services\AuditLogService::class)->log(
            action: 'toggle_auto_submit',
            module: 'ZATCA Device',
            description:
                'تم تفعيل الإرسال التلقائي إلى ZATCA.',
            model: $device,
            newValues: [
                'auto_submit' => true,
                'environment' => $environment,
            ],
            companyId: $device->company_id,
            branchId: $device->branch_id
        );

        return back()->with(
            'success',
            'تم تفعيل الإرسال التلقائي إلى زاتكا.'
        );
    }
    

}
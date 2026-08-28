<x-app-layout>
    <div class="container-fluid py-4" dir="rtl">

        <div class="page-header-card mb-4">
            <div>
                <h3 class="page-title mb-1">النسخ الاحتياطي</h3>
                <p class="page-subtitle mb-0">
                    إنشاء وتحميل وإدارة النسخ الاحتياطية لقاعدة بيانات النظام.
                </p>
            </div>

            @can('backups.create')
                <form method="POST" action="{{ route('backups.create') }}" id="createBackupForm">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        إنشاء نسخة احتياطية الآن
                    </button>
                </form>
            @endcan
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="card shadow-sm wazin-card">
            <div class="card-header wazin-card-header">
                <div>
                    <h5 class="mb-0 fw-bold">قائمة النسخ الاحتياطية</h5>
                    <small>آخر النسخ المحفوظة داخل النظام</small>
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الملف</th>
                                <th>الحجم</th>
                                <th>تاريخ الإنشاء</th>
                                <th width="180">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($files as $index => $file)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="text-start">{{ $file['name'] }}</td>
                                    <td>{{ number_format($file['size'] / 1024, 2) }} KB</td>
                                    <td>{{ date('Y-m-d H:i:s', $file['last_modified']) }}</td>
                                    <td>
                                        @can('backups.download')
                                            <a href="{{ route('backups.download', $file['name']) }}"
                                               class="btn btn-sm btn-success">
                                                تحميل
                                            </a>
                                        @endcan

                                        @can('backups.delete')
                                            <form method="POST"
                                                  action="{{ route('backups.destroy', $file['name']) }}"
                                                  class="d-inline deleteBackupForm">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    حذف
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">
                                        لا توجد نسخ احتياطية حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <style>
        .page-header-card {
            background: linear-gradient(135deg, #071633, #0A1730);
            color: #fff;
            border-radius: 22px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            box-shadow: 0 16px 40px rgba(7, 22, 51, 0.16);
        }

        .page-title {
            color: #fff;
            font-weight: 900;
        }

        .page-subtitle {
            color: #CFEFF3;
            font-weight: 600;
            line-height: 1.8;
        }

        .wazin-card {
            border: 1px solid #E5E7EB;
            border-radius: 20px;
            overflow: hidden;
        }

        .wazin-card-header {
            background: #fff;
            border-bottom: 1px solid #E5E7EB;
            padding: 18px 20px;
        }

        .wazin-card-header h5 {
            color: #071633;
        }

        .wazin-card-header small {
            color: #8EA0B8;
            font-weight: 700;
        }

        thead th {
            background: #071633 !important;
            color: #fff !important;
            border-color: #101F3C !important;
            font-weight: 900;
            white-space: nowrap;
        }

        tbody td {
            vertical-align: middle;
            font-weight: 600;
        }

        .btn-primary {
            background: #2F6BFF !important;
            border-color: #2F6BFF !important;
            font-weight: 900;
            border-radius: 14px;
            padding: 10px 18px;
        }

        .alert {
            border-radius: 16px;
            font-weight: 700;
            line-height: 1.8;
        }
    </style>

    @push('scripts')
        <script>
            $(function () {
                $('#createBackupForm').on('submit', function () {
                    $(this).find('button[type="submit"]')
                        .prop('disabled', true)
                        .text('جاري إنشاء النسخة...');
                });

                $('.deleteBackupForm').on('submit', function (e) {
                    e.preventDefault();

                    let form = this;

                    Swal.fire({
                        icon: 'warning',
                        title: 'حذف النسخة الاحتياطية',
                        text: 'هل أنت متأكد من حذف هذه النسخة؟',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، حذف',
                        cancelButtonText: 'إلغاء',
                        didOpen: function (popup) {
                            popup.setAttribute('dir', 'rtl');
                        }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">{{ $title }}</h6>

        <span class="text-muted no-print">
            عدد الحركات: {{ $rows->count() }}
        </span>
    </div>

    <div class="card-body">
        <div class="table-responsive">

            <table class="table table-bordered table-striped table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>نوع المستند</th>
                        <th>رقم المستند</th>
                        <th>البيان</th>
                        <th>داخل</th>
                        <th>خارج</th>
                        <th>الصافي</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($rows as $index => $row)
                        @php
                            $netFlow = (float) ($row['net_cash_flow'] ?? 0);
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>{{ $row['date'] ?? '-' }}</td>

                            <td>{{ $row['document_type_label'] ?? '-' }}</td>

                            <td>{{ $row['document_no'] ?? '-' }}</td>

                            <td class="text-start">
                                {{ $row['description'] ?? '-' }}
                            </td>

                            <td class="text-end text-success">
                                {{ number_format((float) ($row['cash_in'] ?? 0), 2) }}
                            </td>

                            <td class="text-end text-danger">
                                {{ number_format((float) ($row['cash_out'] ?? 0), 2) }}
                            </td>

                            <td class="text-end fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format(abs($netFlow), 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-muted">
                                لا توجد حركات في هذا القسم حسب الفلاتر المحددة.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="5">الإجمالي</td>

                        <td class="text-end text-success">
                            {{ number_format($rows->sum('cash_in'), 2) }}
                        </td>

                        <td class="text-end text-danger">
                            {{ number_format($rows->sum('cash_out'), 2) }}
                        </td>

                        <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format(abs($net), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>
</div>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
        }
        .header {
            margin-bottom: 14px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px;
        }
        .meta {
            margin: 0;
            color: #475569;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 12px;
            padding: 10px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            font-size: 11px;
        }
        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Appointments Report</p>
        <p class="meta"><strong>Barangay:</strong> {{ $tenant->name }}</p>
        <p class="meta"><strong>Range:</strong> {{ $from }} to {{ $to }}</p>
        <p class="meta"><strong>Generated:</strong> {{ $generatedAt->format('Y-m-d h:i A') }}</p>
    </div>

    <div class="card">
        <p class="section-title">By Status</p>
        @if($byStatus->isEmpty())
            <p class="muted">No data.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byStatus as $status => $count)
                        <tr>
                            <td>{{ ucfirst((string) $status) }}</td>
                            <td>{{ $count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <p class="section-title">By Service</p>
        @if($byServiceDisplay->isEmpty())
            <p class="muted">No data.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($byServiceDisplay as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <p class="section-title">Appointments</p>
        @if($appointments->isEmpty())
            <p class="muted">No appointments in this range.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $appointment)
                        <tr>
                            <td>{{ optional($appointment->scheduled_date)->format('Y-m-d') }}</td>
                            <td>{{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('h:i A') }}</td>
                            <td>{{ $appointment->resident->name ?? 'N/A' }}</td>
                            <td>{{ $appointment->service->name ?? 'N/A' }}</td>
                            <td>{{ ucfirst((string) $appointment->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>

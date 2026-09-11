<!DOCTYPE html>
<html>

<head>
    <title>XML Logs</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 2rem;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            padding: 8px 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        a.download {
            text-decoration: none;
            color: #2563eb;
        }

        details.group {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        details.group.incomplete-group {
            border-color: #f0b429;
        }

        summary.group-header {
            background: #eef2ff;
            padding: 8px 12px;
            font-size: 0.85rem;
            color: #333;
            font-weight: 600;
            cursor: pointer;
            list-style: none;
        }

        details.group.incomplete-group summary.group-header {
            background: #fff7e6;
        }

        summary.group-header::-webkit-details-marker {
            display: none;
        }

        summary.group-header .count {
            color: #777;
            font-weight: 400;
            margin-left: 6px;
        }

        summary.group-header .flag {
            color: #b45309;
            font-weight: 600;
            margin-left: 8px;
        }

        details.group table {
            margin-top: 0.5rem;
        }
    </style>
</head>

<body>
    <h2>XML Log Files ({{ $groups->flatten(1)->count() }})</h2>

    @forelse ($groups as $group)
    <details class="group @if ($group->count() === 1) incomplete-group @endif" open>
        <summary class="group-header">
            {{ $group->first()['group_key'] }}
            <span class="count">({{ $group->count() }} file{{ $group->count() > 1 ? 's' : '' }})</span>
            @if ($group->count() === 1)
            <span class="flag">⚠ incomplete</span>
            @endif
        </summary>
        <table>
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group as $file)
                <tr>
                    <td>{{ $file['name'] }}</td>
                    <td>{{ $file['size'] }}</td>
                    <td>{{ $file['modified'] }}</td>
                    <td>
                        <a class="download" href="{{ route('xml_logs.download', $file['name']) }}">Download</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </details>
    @empty
    <p>No XML log files found.</p>
    @endforelse
</body>

</html>
<!DOCTYPE html>
<html>
<head>
    <title>XML Logs</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        a.download { text-decoration: none; color: #2563eb; }
    </style>
</head>
<body>
    <h2>XML Log Files ({{ $files->count() }})</h2>

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
            @forelse ($files as $file)
                <tr>
                    <td>{{ $file['name'] }}</td>
                    <td>{{ $file['size'] }}</td>
                    <td>{{ $file['modified'] }}</td>
                    <td>
                        <a class="download" href="{{ route('xml_logs.download', $file['name']) }}">Download</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No XML log files found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
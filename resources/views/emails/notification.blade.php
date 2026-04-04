<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject_line }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: #4e1a77; padding: 24px 30px; }
        .header h1 { color: #ffffff; font-size: 18px; margin: 0; font-weight: 600; }
        .header p { color: #d4b5ff; font-size: 13px; margin: 4px 0 0; }
        .body { padding: 30px; }
        .body h2 { color: #1f2937; font-size: 16px; margin: 0 0 12px; }
        .body p { color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0 0 16px; }
        .btn { display: inline-block; background: #4e1a77; color: #ffffff !important; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; }
        .footer { padding: 20px 30px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>eWards PMS</h1>
                <p>Project Management System</p>
            </div>
            <div class="body">
                <h2>{{ $heading }}</h2>
                <p>{!! preg_replace('/(https?:\/\/[^\s<]+)/', '<a href="$1" style="color:#4e1a77;text-decoration:underline;">$1</a>', nl2br(e($body))) !!}</p>
                @if($actionUrl && $actionLabel)
                    <p style="text-align: center; margin-top: 24px;">
                        <a href="{{ $actionUrl }}" class="btn">{{ $actionLabel }}</a>
                    </p>
                @endif
            </div>
            <div class="footer">
                <p>This is an automated notification from eWards PMS. Do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>

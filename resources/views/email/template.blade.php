
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #dddddd;
            padding-bottom: 20px;
        }
        .header img {
            max-width: 100px;
        }
        .content {
            padding: 20px 0;
        }
        .content p {
            font-size: 16px;
            line-height: 1.5;
            color: #333333;
        }
        .footer {
            text-align: center;
            border-top: 1px solid #dddddd;
            padding-top: 20px;
            margin-top: 20px;
            font-size: 14px;
            color: #FBB321;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h4>Elvan Kitchen</h4>
        </div>
        <div class="content">
            
            {!! $body !!}
        </div>
        <div class="footer">
            <p>&copy; 2024 Elvan Kitchen. All rights reserved.</p>
            <p>House No. 91, Road No. 4, Block B, Banani Dhaka-1213</p>
        </div>
    </div>
</body>
</html>

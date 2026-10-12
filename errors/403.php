<?php
    http_response_code(403); // Set HTTP response status to 403
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <link rel="icon" href="/img/logo.jpg">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .error-card {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 480px;
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        h1 {
            font-size: clamp(22px, 5vw, 32px);
            color: #d97706; /* Forbidden / Warning සඳහා තැඹිලි වර්ණය */
            margin-bottom: 15px;
            font-weight: 700;
            line-height: 1.2;
        }

        p {
            font-size: clamp(14px, 3vw, 16px);
            color: #64748b;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        hr {
            border: 0;
            height: 1px;
            background-color: #e2e8f0;
            margin: 20px 0;
        }

        address {
            font-style: normal;
            font-size: 12px;
            color: #94a3b8;
            word-break: break-all;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <h1>403 - Forbidden</h1>
        <?php
            if(isset($forbidden)) {
                echo("<p>" . htmlspecialchars($forbidden) . "</p>");
            } else {
                ?><p>Access to this resource is denied.</p><?php
            }
        ?>
        <hr>
        <address><?php echo($_SERVER['SERVER_SIGNATURE']); ?></address>
    </div>
</body>
</html>

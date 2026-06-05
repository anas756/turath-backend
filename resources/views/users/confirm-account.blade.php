<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background-color: #4CAF50;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 25px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
        }
        .link-alt {
            word-break: break-all;
            color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $title }}</h1>
        
        <p>Bonjour <strong>{{ $userName }}</strong>,</p>
        
        <p>Merci pour votre inscription. Pour finaliser la création de votre compte, merci de confirmer votre adresse e-mail en cliquant sur le bouton ci-dessous :</p>
        
        <center>
            <a href="{{ $confirmationUrl }}" class="button">Vérifier mon adresse e-mail</a>
        </center>
        
        <p>Si le bouton ne s'affiche pas correctement, copiez et collez le lien suivant dans votre navigateur :</p>
        <p class="link-alt">{{ $confirmationUrl }}</p>
        
        <p><strong>Note :</strong> Ce lien est valable pendant 24 heures.</p>
        
        <div class="footer">
            <p>Si vous n'avez pas demandé la création de ce compte, vous pouvez ignorer cet e-mail en toute sécurité.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
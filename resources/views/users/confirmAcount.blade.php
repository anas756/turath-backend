<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    
    <p>Bonjour <strong>{{ $userName }}</strong>,</p>
    
    <p>Merci pour votre inscription. Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse e-mail :</p>
    
    <a href="{{ $confirmationUrl }}" class="button">Vérifier l'adresse e-mail</a>
    
    <p>Si le bouton ne fonctionne pas, vous pouvez également cliquer sur ce lien :</p>
    <p><a href="{{ $confirmationUrl }}">{{ $confirmationUrl }}</a></p>
    
    <p>Ce lien expirera dans 24 heures.</p>
    
    <div class="footer">
        <p>Si vous n'avez pas créé de compte, aucune action supplémentaire n'est requise.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
    </div>
</body>
</html>
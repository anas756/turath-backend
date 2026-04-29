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
        
        <p>Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
        
        <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
        
        <center>
            <a href="{{ $confirmationUrl }}" class="button">Réinitialiser mon mot de passe</a>
        </center>
        
        <p>Si le bouton ne s'affiche pas, copiez et collez ce lien dans votre navigateur :</p>
        <p class="link-alt">{{ $confirmationUrl }}</p>
        
        <p><strong>Attention :</strong> Ce lien est à usage unique et expirera dans 1 heure.</p>
        
        <div class="footer">
            <p>Si vous n'avez pas demandé de réinitialisation, aucune action n'est requise. Votre mot de passe actuel restera inchangé.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
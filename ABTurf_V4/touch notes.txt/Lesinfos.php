<?php
session_start();

/*
 * CONFIGURATION — Changez ces valeurs avant usage
 */
$ADMIN_PASSWORD = 'Sandalex95!lol'; // → remplace par TON mot de passe
$NOTES_FILE = __DIR__ . '/notes.txt';

/* Gestion des actions POST : login, save, logout */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- LOGIN ---
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $pw = $_POST['password'] ?? '';
        if (hash_equals($pw, $ADMIN_PASSWORD)) {
            $_SESSION['is_admin'] = true;
            // Génère un token CSRF
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
            $login_msg = "Connecté en tant qu’administrateur.";
        } else {
            $login_error = "Mot de passe incorrect.";
        }
    }

    // --- SAUVEGARDER ---
    if (isset($_POST['action']) && $_POST['action'] === 'save' && !empty($_SESSION['is_admin'])) {
        // Vérification CSRF
        if (!isset($_POST['csrf']) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            $save_error = "Jeton CSRF invalide. Rechargez la page et réessayez.";
        } else {
            $notes = $_POST['notes'] ?? '';
            // Écrire dans le fichier (verrou exclusif)
            $ok = file_put_contents($NOTES_FILE, $notes, LOCK_EX);
            if ($ok !== false) {
                $save_msg = "✅ Notes sauvegardées.";
            } else {
                $save_error = "Erreur lors de la sauvegarde (droits / chemin ?).";
            }
        }
    }

    // --- LOGOUT ---
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        session_unset();
        session_destroy();
        session_start();
    }
}

/* Charger le contenu actuel des notes */
$notes_content = file_exists($NOTES_FILE) ? file_get_contents($NOTES_FILE) : "";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>ABTurf — Les Infos</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* (Copie ton CSS existant ici) */
    :root{--bg:#0f1724;--card:#0b1220;--muted:#9aa6bf;--accent:#ffb74d;--radius:14px;font-family:Inter,system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial;color-scheme:dark}
    body{margin:0;padding:24px;background:linear-gradient(180deg,#071026 0%, #081028 50%, #041018 100%);color:#e6eef8}
    .container{max-width:1100px;margin:0 auto}
    .card{background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border-radius:var(--radius);padding:18px;margin-bottom:20px}
    textarea{width:100%;min-height:120px;padding:10px;border-radius:8px;border:1px solid rgba(255,255,255,0.1);background:#1b2332;color:white}
    .muted{color:var(--muted)}
    .btn{display:inline-block;padding:8px 12px;border-radius:10px;background:linear-gradient(90deg,#1f6f9f,#2ea3ff);color:white;text-decoration:none;font-weight:700}
    #loginBox{background:rgba(0,0,0,0.45);padding:12px;border-radius:10px;display:none;margin-top:10px}
    .status{margin-top:8px}
  </style>
</head>
<body>
  <div class="container">
    <header style="display:flex;align-items:center;gap:18px;margin-bottom:20px">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="background:linear-gradient(90deg,#ffd09a,#ffb74d);padding:10px 14px;border-radius:10px;color:#081022;font-weight:800">ABTurf</div>
        <div>
          <div style="font-size:18px;font-weight:700">Les Infos</div>
          <div class="muted" style="font-size:13px">Actualités & chevaux à suivre</div>
        </div>
      </div>
    </header>

    <main>
      <section class="card">
        <h2>Chevaux à suivre</h2>
        <p class="muted">Liste des chevaux à surveiller pour les prochaines courses.</p>

        <!-- Formulaire : affichage / édition selon droits -->
        <form method="post" id="notesForm">
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <!-- Mode ADMIN : édition possible -->
            <textarea name="notes" id="notes"><?php echo htmlspecialchars($notes_content); ?></textarea>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'] ?? ''); ?>">
            <div style="margin-top:10px">
              <button type="submit" class="btn">💾 Sauvegarder</button>
              <button type="submit" name="action" value="logout" style="margin-left:8px" class="btn">🔒 Se déconnecter</button>
            </div>
            <?php if (!empty($save_msg)) echo '<div class="status">'.$save_msg.'</div>'; ?>
            <?php if (!empty($save_error)) echo '<div class="status" style="color:#ff7b7b">'.$save_error.'</div>'; ?>
        <?php else: ?>
            <!-- Mode VISITEUR : lecture seule -->
            <textarea readonly><?php echo htmlspecialchars($notes_content); ?></textarea>
            <div style="margin-top:10px">
              <button type="button" class="btn" onclick="document.getElementById('loginBox').style.display='block'">✏️ Se connecter pour modifier</button>
            </div>
        <?php endif; ?>
        </form>

        <!-- Boite de connexion (cachée par défaut) -->
        <div id="loginBox">
          <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <input type="password" name="password" placeholder="Mot de passe admin" style="padding:8px;border-radius:8px;border:1px solid rgba(255,255,255,0.08)">
            <input type="hidden" name="action" value="login">
            <button type="submit" class="btn">Se connecter</button>
            <button type="button" onclick="document.getElementById('loginBox').style.display='none'" class="btn">Annuler</button>
          </form>
          <?php if (!empty($login_error)) echo '<div class="status" style="color:#ff7b7b">'.$login_error.'</div>'; ?>
          <?php if (!empty($login_msg)) echo '<div class="status">'.$login_msg.'</div>'; ?>
        </div>

      </section>
    </main>

    <footer class="muted">© ABTurf 2025 — Jeu responsable.</footer>
  </div>

  <script>
    // Si une erreur de login survient, ouvrir la boîte
    <?php if (!empty($login_error)): ?>
      document.getElementById('loginBox').style.display = 'block';
    <?php endif; ?>
  </script>
</body>
</html>

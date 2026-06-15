<?php
// ==========================================
// 1. BACKEND PHP & DATABASE (API AJAX)
// ==========================================
 $db = new PDO('sqlite:lms_ultra.sqlite');
 $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Création des tables
 $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, phone TEXT, role TEXT, is_pro INTEGER DEFAULT 0, password TEXT)");
 $db->exec("CREATE TABLE IF NOT EXISTS courses (id INTEGER PRIMARY KEY, title TEXT, desc TEXT, type TEXT, content_url TEXT, created_by INTEGER, verified INTEGER DEFAULT 0)");
 $db->exec("CREATE TABLE IF NOT EXISTS grades (id INTEGER PRIMARY KEY, student_id INTEGER, course_id INTEGER, score INTEGER, appreciation TEXT, decision TEXT, is_released INTEGER DEFAULT 0)");
 $db->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY, sender_id INTEGER, receiver_id INTEGER, course_id INTEGER, message TEXT, is_ultimatum INTEGER DEFAULT 0)");
 $db->exec("CREATE TABLE IF NOT EXISTS pro_requests (id INTEGER PRIMARY KEY, user_id INTEGER, doc_url TEXT, status TEXT DEFAULT 'pending')");

// API Routing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    
    if ($action === 'login') {
        $name = $_POST['name']; $role = $_POST['role']; $email = $_POST['email'];
        $stmt = $db->prepare("SELECT * FROM users WHERE name = ? AND role = ?"); $stmt->execute([$name, $role]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $db->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, ?)")->execute([$name, $email, $role]);
            $user = ['id' => $db->lastInsertId(), 'name' => $name, 'role' => $role, 'is_pro' => 0];
        }
        echo json_encode($user); exit;
    }

    if ($action === 'add_course') {
        $verified = ($_POST['role'] === 'academy' || $_POST['role'] === 'professor') ? 1 : 0;
        $db->prepare("INSERT INTO courses (title, desc, type, content_url, created_by, verified) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$_POST['title'], $_POST['desc'], $_POST['type'], $_POST['url'], $_POST['user_id'], $verified]);
        echo json_encode(['status' => 'success']); exit;
    }

    if ($action === 'submit_grade') {
        $db->prepare("INSERT OR REPLACE INTO grades (student_id, course_id, score, appreciation, decision, is_released) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$_POST['student_id'], $_POST['course_id'], $_POST['score'], $_POST['appreciation'], $_POST['decision'], $_POST['release']]);
        echo json_encode(['status' => 'success']); exit;
    }

    if ($action === 'request_pro') {
        $db->prepare("INSERT INTO pro_requests (user_id, doc_url) VALUES (?, ?))->execute([$_POST['user_id'], $_POST['doc_url']]);
        echo json_encode(['status' => 'success']); exit;
    }
}

 $courses = $db->query("SELECT c.*, u.name as author FROM courses c LEFT JOIN users u ON c.created_by = u.id")->fetchAll(PDO::FETCH_ASSOC);
 $users = $db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Ultra | Udemy x iOS Design</title>
    <!-- Chart.js pour les graphes -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- html2pdf pour exporter les relevés -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        /* ============ CSS VARIABLES & IOS LIQUID GLASS ============ */
        :root {
            --bg: #f2f2f7; --text: #1c1c1e; --glass-bg: rgba(255, 255, 255, 0.7); --glass-border: rgba(255, 255, 255, 0.8);
            --primary: #007aff; --red: #ff3b30; --green: #34c759; --purple: #af52de; --orange: #ff9500;
            --shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        [data-theme="dark"] {
            --bg: #000000; --text: #f5f5f7; --glass-bg: rgba(30, 30, 30, 0.7); --glass-border: rgba(50, 50, 50, 0.8);
            --shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", sans-serif; transition: background 0.3s, color 0.3s; }
        body { background: var(--bg); color: var(--text); min-height: 100vh; }
        
        .glass { background: var(--glass-bg); backdrop-filter: blur(30px) saturate(180%); -webkit-backdrop-filter: blur(30px) saturate(180%); border: 1px solid var(--glass-border); border-radius: 24px; box-shadow: var(--shadow); }
        .btn-3d { padding: 12px 24px; border: none; border-radius: 16px; font-weight: 700; cursor: pointer; color: white; box-shadow: 0 6px 0 rgba(0,0,0,0.1), 0 8px 20px rgba(0,0,0,0.1); transition: all 0.2s; }
        .btn-3d:active { transform: translateY(4px); box-shadow: 0 2px 0 rgba(0,0,0,0.1); }
        .btn-blue { background: var(--primary); } .btn-red { background: var(--red); } .btn-green { background: var(--green); } .btn-purple { background: var(--purple); }

        input, select, textarea { width: 100%; padding: 12px; margin: 8px 0; border-radius: 16px; border: 1px solid var(--glass-border); background: var(--glass-bg); color: var(--text); font-size: 16px; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .hidden { display: none !important; }
        
        /* NAV & LAYOUT */
        .nav-bar { display: flex; justify-content: space-between; align-items: center; padding: 15px 30px; position: sticky; top: 0; z-index: 100; border-radius: 0 0 24px 24px; }
        .nav-links { display: flex; gap: 20px; }
        .nav-item { cursor: pointer; font-weight: 600; color: var(--primary); border-bottom: 2px solid transparent; }
        .nav-item:hover { border-bottom: 2px solid var(--primary); }
        .toggle-theme { cursor: pointer; font-size: 24px; }

        /* CARDS (UDEMY STYLE) */
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 20px; }
        .course-card { overflow: hidden; transition: transform 0.3s; cursor: pointer; }
        .course-card:hover { transform: scale(1.03); }
        .card-header { height: 140px; background: linear-gradient(135deg, var(--primary), var(--purple)); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; border-radius: 24px 24px 0 0; }
        .card-body { padding: 20px; }
        .badge { background: var(--orange); color: white; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: bold; }

        /* DASHBOARD LAYOUT */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px; }
        @media (max-width: 768px) { .dashboard-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <!-- LOGIN SCREEN -->
    <div id="auth-screen" class="container" style="display:flex; justify-content:center; align-items:center; height:100vh;">
        <div class="glass" style="padding: 40px; width: 450px; text-align: center;">
            <h1 style="margin-bottom: 20px; font-size: 30px;">🎓 LMS Ultra</h1>
            <p style="margin-bottom: 20px; opacity: 0.7;">Inspired by Udemy, OpenClassrooms & iOS</p>
            <input type="text" id="regName" placeholder="Nom complet" required>
            <input type="email" id="regEmail" placeholder="Email professionnel" required>
            <input type="text" id="regPhone" placeholder="Téléphone (Optionnel)">
            <select id="regRole">
                <option value="visitor">Visiteur / Curieux</option>
                <option value="student">Étudiant</option>
                <option value="professor">Professeur</option>
                <option value="academy">Académie / Institution</option>
            </select>
            <br><br>
            <button class="btn-3d btn-blue" style="width:100%" onclick="login()">Connexion / Inscription</button>
        </div>
    </div>

    <!-- MAIN APP -->
    <div id="main-app" class="hidden">
        <div class="nav-bar glass">
            <h2>🎓 LMS Ultra</h2>
            <div class="nav-links">
                <span class="nav-item" onclick="nav('home')">Accueil</span>
                <span class="nav-item" onclick="nav('dashboard')">Mon Espace</span>
                <span class="nav-item" onclick="nav('classroom')">Salle de Classe</span>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <span class="toggle-theme" onclick="toggleTheme()">🌙</span>
                <span id="user-badge" style="font-weight:bold; color:var(--primary)"></span>
                <button class="btn-3d btn-red" style="padding:8px 16px; font-size:14px;" onclick="logout()">X</button>
            </div>
        </div>

        <div class="container">
            <!-- HOME (COURS) -->
            <div id="view-home">
                <h1 style="margin-top:20px;">Explorez des milliers de cours</h1>
                <div class="course-grid" id="coursesGrid"></div>
            </div>

            <!-- DASHBOARD (PROFESSOR / ACADEMY / STUDENT) -->
            <div id="view-dashboard" class="hidden">
                <div class="dashboard-grid">
                    <div>
                        <!-- PROFESSEUR / ACADEMY PANEL -->
                        <div id="panel-creator" class="glass hidden" style="padding:20px; margin-bottom:20px;">
                            <h3>➕ Ajouter un Cours / Ressource</h3>
                            <input type="text" id="cTitle" placeholder="Titre du cours">
                            <textarea id="cDesc" placeholder="Description du cours"></textarea>
                            <select id="cType"><option value="video">Vidéo (Lien Youtube/Vimeo)</option><option value="pdf">PDF (Lien drive)</option><option value="link">Lien Externe</option></select>
                            <input type="text" id="cUrl" placeholder="URL de la ressource">
                            <button class="btn-3d btn-green" onclick="addCourse()">Publier</button>
                        </div>

                        <!-- NOTATION & JURY PANEL -->
                        <div id="panel-grade" class="glass hidden" style="padding:20px; margin-bottom:20px;">
                            <h3>📊 Gestion des Notes & Jury</h3>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <select id="gStudent"></select><select id="gCourse"></select>
                            </div>
                            <input type="number" id="gScore" placeholder="Score (0-100)" min="0" max="100">
                            <input type="text" id="gAppreciation" placeholder="Appréciation (Ex: Excellent, Peut mieux faire)">
                            <select id="gDecision"><option value="Validé">Validé</option><option value="Non Validé">Non Validé</option><option value="Rattrapage">Rattrapage (Ultimatum)</option></select>
                            <label><input type="checkbox" id="gRelease"> Publier le relevé (L'étudiant le verra)</label>
                            <button class="btn-3d btn-purple" onclick="submitGrade()">Enregistrer la Note</button>
                        </div>

                        <!-- VISITOR PRO REQUEST -->
                        <div id="panel-pro" class="glass hidden" style="padding:20px; margin-bottom:20px;">
                            <h3>🏆 Devenir Créateur Certifié</h3>
                            <p>Pour ajouter des cours, prouvez vos qualifications (Diplôme, Certificat).</p>
                            <input type="text" id="proDoc" placeholder="Lien vers votre CV ou Certificat (PDF)">
                            <button class="btn-3d btn-blue" onclick="requestPro()">Envoyer la demande</button>
                        </div>

                        <!-- LISTE DES RELEVES (ETUDIANT) -->
                        <div id="panel-student-grades" class="glass hidden" style="padding:20px;">
                            <h3>📜 Mes Relevés de Notes</h3>
                            <p style="font-size:14px; opacity:0.7;">Les notes confidentielles n'apparaissent que si le prof les a publiées.</p>
                            <div id="student-grades-list"></div>
                            <button class="btn-3d btn-green" style="margin-top:20px;" onclick="exportPDF()">📥 Télécharger en PDF (Pour Signature Campus)</button>
                        </div>
                    </div>
                    
                    <div>
                        <!-- GRAPHIQUE -->
                        <div class="glass" style="padding:20px; margin-bottom:20px;">
                            <h4>📈 Progression</h4>
                            <canvas id="progressChart" width="400" height="400"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CLASSROOM (MEET + ULTIMATUMS) -->
            <div id="view-classroom" class="hidden">
                <div class="dashboard-grid">
                    <div class="glass" style="padding:20px; height: 500px;">
                        <h3>📹 Salle de Visioconférence (Type Google Meet)</h3>
                        <iframe style="width:100%; height:80%; border:none; border-radius:16px; margin-top:10px;" src="https://meet.jit.si/LMS-Ultra-Classroom" allow="camera; microphone; fullscreen"></iframe>
                    </div>
                    <div class="glass" style="padding:20px; max-height: 500px; overflow-y: auto;">
                        <h3>💬 Commentaires & Ultimatums</h3>
                        <div id="chat-box" style="height: 300px; overflow-y: auto; background:var(--bg); border-radius:16px; padding:10px; margin-bottom:10px;"></div>
                        <input type="text" id="chatMsg" placeholder="Votre message...">
                        <button class="btn-3d btn-red" onclick="sendChat(true)">⚠️ Ultimatum</button>
                        <button class="btn-3d btn-blue" onclick="sendChat(false)">Envoyer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Data injected by PHP
        let courses = <?= json_encode($courses) ?>;
        let users = <?= json_encode($users) ?>;
        let currentUser = null;
        let chartInstance = null;

        // Theme management
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';
            html.setAttribute('data-theme', isDark ? 'light' : 'dark');
            document.querySelector('.toggle-theme').innerText = isDark ? '🌙' : '☀️';
        }
        // Auto detect system theme
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        // Auth
        function login() {
            const name = document.getElementById('regName').value;
            const email = document.getElementById('regEmail').value;
            const role = document.getElementById('regRole').value;
            if(!name || !email) return alert('Remplissez les champs');

            const formData = new FormData();
            formData.append('action', 'login'); formData.append('name', name); formData.append('email', email); formData.append('role', role);
            
            fetch('index.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                currentUser = data;
                document.getElementById('auth-screen').classList.add('hidden');
                document.getElementById('main-app').classList.remove('hidden');
                document.getElementById('user-badge').innerText = `${currentUser.name} (${currentUser.role})`;
                initUI();
            });
        }

        function logout() { location.reload(); }

        function nav(view) {
            ['home', 'dashboard', 'classroom'].forEach(v => document.getElementById(`view-${v}`).classList.add('hidden'));
            document.getElementById(`view-${view}`).classList.remove('hidden');
        }

        function initUI() {
            // Render Courses
            document.getElementById('coursesGrid').innerHTML = courses.map(c => `
                <div class="course-card glass">
                    <div class="card-header" style="background: linear-gradient(135deg, ${c.type==='video'?'var(--red)':'var(--primary)'}, var(--purple))">
                        ${c.type === 'video' ? '▶️' : '📄'}
                    </div>
                    <div class="card-body">
                        ${c.verified ? '<span class="badge">Certifié</span>' : ''}
                        <h3>${c.title}</h3>
                        <p style="font-size:14px; opacity:0.7; margin:10px 0;">Par ${c.author || 'Inconnu'}</p>
                        <a href="${c.content_url}" target="_blank" class="btn-3d btn-blue" style="padding:8px 16px; font-size:14px; text-decoration:none;">Suivre le cours</a>
                        <a href="${c.content_url}" download class="btn-3d btn-green" style="padding:8px 16px; font-size:14px; text-decoration:none; margin-left:5px;">Télécharger</a>
                    </div>
                </div>
            `).join('');

            // Show role specific panels
            if(['professor', 'academy'].includes(currentUser.role)) {
                document.getElementById('panel-creator').classList.remove('hidden');
                document.getElementById('panel-grade').classList.remove('hidden');
                populateGradeSelects();
            } else if(currentUser.role === 'student') {
                document.getElementById('panel-student-grades').classList.remove('hidden');
                loadStudentGrades();
            } else if(currentUser.role === 'visitor') {
                document.getElementById('panel-pro').classList.remove('hidden');
            }

            initChart();
        }

        function populateGradeSelects() {
            let sHtml = '<option value="">-- Étudiant --</option>';
            users.filter(u => u.role === 'student').forEach(u => sHtml += `<option value="${u.id}">${u.name}</option>`);
            document.getElementById('gStudent').innerHTML = sHtml;

            let cHtml = '<option value="">-- Cours --</option>';
            courses.forEach(c => cHtml += `<option value="${c.id}">${c.title}</option>`);
            document.getElementById('gCourse').innerHTML = cHtml;
        }

        function addCourse() {
            const formData = new FormData();
            formData.append('action', 'add_course');
            formData.append('title', document.getElementById('cTitle').value);
            formData.append('desc', document.getElementById('cDesc').value);
            formData.append('type', document.getElementById('cType').value);
            formData.append('url', document.getElementById('cUrl').value);
            formData.append('user_id', currentUser.id);
            formData.append('role', currentUser.role);
            
            fetch('index.php', { method: 'POST', body: formData }).then(() => { alert('Cours ajouté!'); location.reload(); });
        }

        function requestPro() {
            const formData = new FormData();
            formData.append('action', 'request_pro');
            formData.append('user_id', currentUser.id);
            formData.append('doc_url', document.getElementById('proDoc').value);
            fetch('index.php', { method: 'POST', body: formData }).then(() => alert('Demande envoyée aux administrateurs !'));
        }

        function submitGrade() {
            const formData = new FormData();
            formData.append('action', 'submit_grade');
            formData.append('student_id', document.getElementById('gStudent').value);
            formData.append('course_id', document.getElementById('gCourse').value);
            formData.append('score', document.getElementById('gScore').value);
            formData.append('appreciation', document.getElementById('gAppreciation').value);
            formData.append('decision', document.getElementById('gDecision').value);
            formData.append('release', document.getElementById('gRelease').checked ? 1 : 0);
            
            fetch('index.php', { method: 'POST', body: formData }).then(() => alert('Note enregistrée!'));
        }

        function loadStudentGrades() {
            // Simulated grades loading for chart & PDF
            const list = document.getElementById('student-grades-list');
            list.innerHTML = '<p>Aucune note publiée pour le moment.</p>'; // Will be populated by AJAX in real app
        }

        // Chart.js Graph
        function initChart() {
            const ctx = document.getElementById('progressChart').getContext('2d');
            if(chartInstance) chartInstance.destroy();
            
            // Random data for demo
            const data = courses.map(() => Math.floor(Math.random() * 100));
            
            chartInstance = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: courses.map(c => c.title.substring(0, 15)),
                    datasets: [{
                        label: 'Mon Progression (%)',
                        data: data,
                        backgroundColor: 'rgba(0, 122, 255, 0.2)',
                        borderColor: 'rgba(0, 122, 255, 1)',
                        pointBackgroundColor: 'var(--primary)'
                    }]
                },
                options: { scales: { r: { max: 100, beginAtZero: true } } }
            });
        }

        // Chat & Ultimatums
        function sendChat(isUltimatum) {
            const msg = document.getElementById('chatMsg').value;
            const box = document.getElementById('chat-box');
            const div = document.createElement('div');
            div.style.marginBottom = '10px';
            div.style.padding = '10px';
            div.style.borderRadius = '12px';
            div.style.background = isUltimatum ? 'var(--red)' : 'var(--glass-bg)';
            div.style.color = isUltimatum ? 'white' : 'var(--text)';
            div.innerHTML = `<b>${currentUser.name}:</b> ${msg} ${isUltimatum ? '<br><b>⚠️ ULTIMATUM DE TRAVAIL ⚠️</b>' : ''}`;
            box.appendChild(div);
            document.getElementById('chatMsg').value = '';
        }

        // PDF Export (Relevé de notes)
        function exportPDF() {
            const element = document.getElementById('student-grades-list');
            const opt = {
                margin: 1,
                filename: `Releve_Notes_${currentUser.name}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>

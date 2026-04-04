<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages - JSTACK</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .inbox-container { max-width: 680px; margin: 40px auto; padding: 0 16px; }
    .inbox-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .inbox-header h2 { margin: 0; font-size: 22px; color: #111; }
    .convo-card {
      display: flex; align-items: center; gap: 16px;
      background: white; border: 1px solid #eee; border-radius: 12px;
      padding: 16px 20px; margin-bottom: 12px; cursor: pointer;
      transition: all 0.2s ease; text-decoration: none; color: inherit;
    }
    .convo-card:hover { border-color: #0a66c2; box-shadow: 0 4px 16px rgba(10,102,194,0.12); transform: translateY(-2px); }
    .avatar {
      width: 48px; height: 48px; border-radius: 50%;
      background: linear-gradient(135deg, #0a66c2, #0052a3);
      display: flex; align-items: center; justify-content: center;
      color: white; font-weight: 700; font-size: 18px; flex-shrink: 0;
    }
    .convo-info { flex: 1; min-width: 0; }
    .convo-name { font-weight: 600; font-size: 15px; color: #111; margin-bottom: 4px; }
    .convo-preview { font-size: 13px; color: #777; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .convo-meta { text-align: right; flex-shrink: 0; }
    .convo-time { font-size: 11px; color: #aaa; white-space: nowrap; }
    .role-badge { font-size: 10px; background: #e7f3ff; color: #0a66c2; padding: 2px 7px; border-radius: 10px; font-weight: 600; text-transform: capitalize; display: inline-block; margin-top: 4px; }
    .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
    .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
    .empty-state p { font-size: 14px; }
    .loading-pulse { text-align: center; padding: 40px; color: #888; }
  </style>
</head>
<body>

<header class="navbar">
  <h2>JSTACK <span style="font-weight:normal;opacity:0.8;">| Messages</span></h2>
  <nav>
    <a href="../index.html" style="color:white;text-decoration:none;">← Home</a>
  </nav>
</header>

<div class="inbox-container">
  <div class="inbox-header">
    <h2>💬 Inbox</h2>
    <span id="convo-count" style="font-size:13px;color:#888;"></span>
  </div>
  <div id="conversations-list">
    <div class="loading-pulse">Loading conversations...</div>
  </div>
</div>

<footer><p>© 2026 JSTACK</p></footer>

<script src="../assets/js/main.js?v=1.2"></script>
<script>
  async function init() {
    // Ensure user is logged in
    await requireAuth();

    const list = document.getElementById('conversations-list');
    const countEl = document.getElementById('convo-count');

    // Fetch conversations from API
    const res = await apiGet(`${API}/messages.php`);

    // The API returns a plain array of conversations (not wrapped in {data:[]})
    const data = Array.isArray(res) ? res :
                 (res && Array.isArray(res.data)) ? res.data : [];

    if (!data.length) {
      list.innerHTML = `
        <div class="empty-state">
          <div class="icon">📭</div>
          <p>No conversations yet.<br>Start chatting with an employer or seeker!</p>
        </div>`;
      countEl.textContent = '';
      return;
    }

    countEl.textContent = `${data.length} conversation${data.length !== 1 ? 's' : ''}`;

    list.innerHTML = data.map(c => {
      const partner = c.partner || {};
      const lastMsg = c.last_message || {};
      const initial = (partner.name || '?').charAt(0).toUpperCase();
      const preview = lastMsg.message
        ? escHtml(lastMsg.message.slice(0, 80)) + (lastMsg.message.length > 80 ? '…' : '')
        : '<em style="color:#bbb;">No messages yet</em>';
      const timeStr = lastMsg.sent_at
        ? new Date(lastMsg.sent_at).toLocaleString([], { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' })
        : '';

      return `
        <a class="convo-card" href="chat.php?with=${partner.id}">
          <div class="avatar">${initial}</div>
          <div class="convo-info">
            <div class="convo-name">${escHtml(partner.name || 'Unknown')}</div>
            <div class="convo-preview">${preview}</div>
          </div>
          <div class="convo-meta">
            <div class="convo-time">${timeStr}</div>
            <div class="role-badge">${escHtml(partner.role || '')}</div>
          </div>
        </a>`;
    }).join('');
  }

  init();
</script>
</body>
</html>
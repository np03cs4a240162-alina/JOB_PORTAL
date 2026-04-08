(async () => {
  const tests = [
    { name: "Check Sidebar Avatar", selector: "#avatar-initials" },
    { name: "Verify Navbar Logo", selector: ".navbar h2" },
    { name: "Stat Cards Present", selector: ".stat-card" },
    { name: "Recent Jobs List", selector: "#jobs-list, #application-list" }
  ];

  console.log("%c JSTACK UI TEST RUNNER ", "background: #6366f1; color: #fff; padding: 5px; font-weight: bold; border-radius: 5px;");

  for (const t of tests) {
    const el = document.querySelector(t.selector);
    if (el) {
      console.log(`✅ %c${t.name}%c: Element found (%c${t.selector}%c)`, "font-weight: bold", "color: #333", "color: blue", "color: #333");
    } else {
      console.warn(`❌ %c${t.name}%c: Element NOT found (%c${t.selector}%c)`, "font-weight: bold", "color: #333", "color: red", "color: #333");
    }
  }

  // API Integration Check
  try {
    const apiRes = await fetch("api/jobs.php").then(r => r.json());
    if (apiRes.success) {
      console.log("✅ %cAPI Integration%c: Job fetch success", "font-weight: bold", "color: #333");
    } else {
      console.error("❌ %cAPI Integration%c: Job fetch error", "font-weight: bold", "color: #333", apiRes.error);
    }
  } catch (err) {
      console.error("❌ %cAPI Integration%c: API unreachable from browser", "font-weight: bold", "color: #333");
  }

  console.log("%c Tests Completed! ", "background: #10b981; color: #fff; padding: 3px; font-weight: bold; border-radius: 3px;");
})();

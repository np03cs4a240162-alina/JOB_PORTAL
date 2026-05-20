# RBAC Test Examples

Use the commands below to test RBAC behavior locally. These examples use `curl` with a cookie jar to persist PHP session cookies. Replace `http://localhost/NewJob/api` with your server base URL.

1) Employer: Post a job (requires logged-in employer session)

```bash
curl -c cookies.txt -b cookies.txt -X POST "http://localhost/NewJob/api/jobs.php" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Job","company":"Acme","location":"Remote","description":"Test","salary":"1000","category":"IT","type":"Full Time","workplace":"Remote","industry":"IT","experience_level":"entry"}'
```

2) Seeker: Apply to a job

```bash
curl -c cookies.txt -b cookies.txt -X POST "http://localhost/NewJob/api/applications.php?action=apply" \
  -H "Content-Type: application/json" \
  -d '{"job_id":1,"resume_note":"I am interested"}'
```

3) Employer: Update application status (only employer owning the job or admin)

```bash
curl -c cookies.txt -b cookies.txt -X POST "http://localhost/NewJob/api/applications.php?action=update-status" \
  -H "Content-Type: application/json" \
  -d '{"id":1,"status":"accepted"}'
```

4) Admin: List all users

```bash
curl -c cookies.txt -b cookies.txt "http://localhost/NewJob/api/users.php"
```

Notes:
- To login first, use the existing login flow (`api/auth.php` or `auth/login.html`) to create the session cookie stored in `cookies.txt`.
- On Windows PowerShell, use `Invoke-RestMethod` and `-WebSession` to persist cookies.

# Git commands — push to a new private repository

Use these from your **local project folder** (where you already have the code and git history).

---

## 1. Add the new remote and push

If this is the **first** time linking to the new repo:

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_PRIVATE_REPO.git
git branch -M main
git push -u origin main
```

Replace:
- `YOUR_USERNAME` — your GitHub username or org
- `YOUR_PRIVATE_REPO` — the new private repository name

---

## 2. If `origin` already exists (e.g. old repo)

Point `origin` to the new private repo, then push:

```bash
git remote set-url origin https://github.com/YOUR_USERNAME/YOUR_PRIVATE_REPO.git
git push -u origin main
```

---

## 3. Using SSH instead of HTTPS

```bash
git remote add origin git@github.com:YOUR_USERNAME/YOUR_PRIVATE_REPO.git
git branch -M main
git push -u origin main
```

---

## 4. Create the private repo on GitHub first

1. Go to [github.com/new](https://github.com/new).
2. Set **Repository name** (e.g. `sober-inventory`).
3. Choose **Private**.
4. Do **not** add README, .gitignore, or license (you already have them).
5. Click **Create repository**.
6. Run the commands from section 1 or 2 with the URL GitHub shows.

---

## 5. Authentication

- **HTTPS:** GitHub may ask for username + **Personal Access Token** (not your account password). Create one: [Settings → Developer settings → Personal access tokens](https://github.com/settings/tokens).
- **SSH:** Ensure your SSH key is added to GitHub: [Settings → SSH and GPG keys](https://github.com/settings/keys).

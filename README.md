# 🚀 **LARIS** — The Ultimate Laravel Artisan Companion You Didn’t Know You Needed! 🎩✨  



```
          _____            _____                    _____                    _____                    _____          
         /\    \          /\    \                  /\    \                  /\    \                  /\    \         
        /::\____\        /::\    \                /::\    \                /::\    \                /::\    \        
       /:::/    /       /::::\    \              /::::\    \               \:::\    \              /::::\    \       
      /:::/    /       /::::::\    \            /::::::\    \               \:::\    \            /::::::\    \      
     /:::/    /       /:::/\:::\    \          /:::/\:::\    \               \:::\    \          /:::/\:::\    \     
    /:::/    /       /:::/__\:::\    \        /:::/__\:::\    \               \:::\    \        /:::/__\:::\    \    
   /:::/    /       /::::\   \:::\    \      /::::\   \:::\    \              /::::\    \       \:::\   \:::\    \   
  /:::/    /       /::::::\   \:::\    \    /::::::\   \:::\    \    ____    /::::::\    \    ___\:::\   \:::\    \  
 /:::/    /       /:::/\:::\   \:::\    \  /:::/\:::\   \:::\____\  /\   \  /:::/\:::\    \  /\   \:::\   \:::\    \ 
/:::/____/       /:::/  \:::\   \:::\____\/:::/  \:::\   \:::|    |/::\   \/:::/  \:::\____\/::\   \:::\   \:::\____\
\:::\    \       \::/    \:::\  /:::/    /\::/   |::::\  /:::|____|\:::\  /:::/    \::/    /\:::\   \:::\   \::/    /
 \:::\    \       \/____/ \:::\/:::/    /  \/____|:::::\/:::/    /  \:::\/:::/    / \/____/  \:::\   \:::\   \/____/ 
  \:::\    \               \::::::/    /         |:::::::::/    /    \::::::/    /            \:::\   \:::\    \     
   \:::\    \               \::::/    /          |::|\::::/    /      \::::/____/              \:::\   \:::\____\    
    \:::\    \              /:::/    /           |::| \::/____/        \:::\    \               \:::\  /:::/    /    
     \:::\    \            /:::/    /            |::|  ~|               \:::\    \               \:::\/:::/    /     
      \:::\    \          /:::/    /             |::|   |                \:::\    \               \::::::/    /      
       \:::\____\        /:::/    /              \::|   |                 \:::\____\               \::::/    /       
        \::/    /        \::/    /                \:|   |                  \::/    /                \::/    /        
         \/____/          \/____/                  \|___|                   \/____/                  \/____/         
                                                                                                                     
```
---

## **🔥 What the Heck is Laris?**  

Laris is **not just another CLI tool**—it’s **your Laravel Artisan on steroids** �💉.  

Tired of typing `php artisan` **a million times a day**?  
Annoyed by forgetting command syntax **right after you Google it**?  
Wish you had a **smarter, faster, cooler** way to manage Laravel projects?  

**Enter Laris.**  

It’s like if **Artisan had a caffeine overdose** and decided to **automate your life**.  

---

## **💎 Why Laris is the GOAT (Greatest Of All Time)**  

✅ **Shorter Commands** – `laris` instead of `php artisan` (because laziness = efficiency).  
✅ **Project Switching** – Jump between Laravel projects like a **multitasking wizard**.  
✅ **Database Magic** – Run queries, fake data, and **play God with your DB**.  
✅ **Deploy Like a Pro** – One command to **push, migrate, and flex**.  
✅ **Git & Composer Shortcuts** – Because typing `git push origin main` is **so 2020**.  
✅ **Backup & Docs** – Automate boring stuff **before you forget**.  
✅ **Extensible AF** – Add your own commands and **make it yours**.  

---

## **⚡ Laris in Action: The Cheat Sheet**  

| Command                  | What It Does (Because You’re Too Lazy to Guess) |  
|--------------------------|------------------------------------------------|  
| `laris list`             | Lists all commands (duh) |  
| `laris select`           | Switch between Laravel projects **like a boss** |  
| `laris db list-tables`   | Shows all DB tables (so you can judge them) |  
| `laris db select users`  | Runs `SELECT * FROM users` (but fancier) |  
| `laris db fake-data`     | Fill your DB with **beautiful lies** (seeding) |  
| `laris backup run`       | Backs up your project (before you break it) |  
| `laris deploy`           | Deploys with **one keystroke** (no excuses now) |  
| `laris git push`         | Pushes to Git **without typing the whole thing** |  
| `laris npm run dev`      | Runs npm **because frontend is hard** |  
| `laris docs generate`    | Generates docs (so you don’t have to) |  

---

## **🚀 Installation: Get Laris in 3 Seconds**  

### **Option 1: The "I’m in a Hurry" Method**  
```bash
composer global require laris/cli
``

### **Option 2: The "I’m in a Hurry" Method**  
```bash  
git clone https://github.com/your-repo/laris.git && cd laris  
```  

### **Option 3: The "I Like Aliases" Method**  
Add this to your `.bashrc` or `.zshrc`:  
```bash  
alias laris='php /path/to/laris/src/Application.php'  
```  

### **Option 4: The "I Want Global Access" Method**  
Symlink it to `/usr/local/bin` (for **ultimate power**):  
```bash  
ln -s /path/to/laris/src/Application.php /usr/local/bin/laris  
```  

Now just type `laris` **anywhere, anytime**.  

---

## **🎮 Usage: How to Not Screw It Up**  

1. **Navigate to a Laravel project** (or use `laris select` to pick one).  
2. **Run commands like a pro**:  
   ```bash  
   laris db list-tables   # See what’s in your DB  
   laris db fake-data     # Fill it with nonsense  
   laris deploy           # Ship it!  
   ```  
3. **Profit.** 💰  

---

## **🧑‍💻 For Nerds Who Love Code**  

Laris is built with:  
- **Pure PHP OOP** (because we’re not animals)  
- **Modular Commands** in `src/Commands/` (add your own!)  
- **DB Helpers** in `src/Library/DB/` (SQL without the pain)  
- **Concerns for Reusable Logic** (DRY is life)  

Want to **add a command**? Just drop a new class in `src/Commands/`.  

---

## **🤝 Contribute (Because Sharing is Caring)**  

1. **Fork it**  
2. **Add a cool feature**  
3. **Submit a PR**  
4. **Brag about it on Twitter**  

---

## **📜 License**  

MIT. Do whatever. Just don’t blame us if your DB explodes.  

---

## **🎤 Final Words**  

Laris exists because **typing `php artisan` is a crime against productivity**.  

Now go **automate your workflow**, **impress your coworkers**, and **spend more time drinking coffee**.  

**Happy coding!** 🚀🔥  

*(P.S. If you break something, just run `laris backup run` first. You’re welcome.)*  

---  

Need **more details?** Check the `docs/` folder or **yell at us on GitHub**. 🎤💬

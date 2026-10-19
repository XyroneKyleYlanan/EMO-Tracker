# Installing EMO Tracker at the EMO office

Office mode: EMO Tracker starts by itself when the laptop turns on, and a desktop icon opens it. No terminal windows, and the laptop doesn't need Node.js. It takes about an hour.

## Before you go (on the Mac)

1. **Make the USB folder.** In Terminal, in the EMOTracker folder:
   ```
   office/make-usb-copy.command --with-data
   ```
   It makes **Desktop › EMO Tracker USB › EMOTracker** (about 64 MB), with a backup of the Mac's data in `office/data`. Copy that **EMOTracker** folder to the USB drive.
2. **Download Laragon** (Full, 64-bit) from [laragon.org/download](https://laragon.org/download) onto the USB too, so you don't depend on the office Wi-Fi.
3. **Bring:** the USB drive, the MacBook and its charger (the backup plan), a phone, and a list of the EMO staff who will use it: name, email, and role (Administrator, Officer or Staff).

## At the office

### 1. Choose the server laptop
EMO Tracker runs on **one** laptop, and every other device opens it from there. Choose the laptop that:
- stays in the office during office hours, plugged in (other devices can't reach EMO Tracker while it's off or away)
- runs Windows 10 or 11

### 2. Install Laragon (10 minutes)
1. Run the Laragon installer and keep its folder: `C:\laragon`.
2. Open Laragon. **Menu › Preferences › General:** tick **Start All automatically** and **Run Laragon when Windows starts**.
3. *(Optional)* **Preferences › Services & Ports:** untick **Apache**. EMO Tracker doesn't use it.
4. Click **Start All**.
   - **Windows Security asks about mysqld or httpd:** click **Cancel**. They don't need the network.
   - **A license reminder appears:** close it. Laragon is free for non-commercial use.
5. **Menu › PHP › Version:** choose **8.3** (8.4 also works).

### 3. Copy EMO Tracker (5 minutes)
Copy the **EMOTracker** folder from the USB drive to `C:\`, so you get `C:\EMOTracker`.

### 4. Run the setup (5 minutes)
1. Open `C:\EMOTracker\office`, right-click **Setup EMO Tracker.bat**, choose **Run as administrator**, then **Yes**.
2. When it asks **Load it?** (the data backup), press **Y**.
3. It ends with **Done** and the addresses. **Write down** the "Other devices" address that matches the Wi-Fi, usually one starting with 10. or 192.168.

If it stops:
| Message | Fix |
|---|---|
| PHP is missing these modules | Laragon › Menu › PHP › Extensions, click each one, then run the setup again |
| The database could not be prepared | In Laragon, click Start All, then run the setup again |
| PHP was not found / needs PHP 8.3 | Step 2.5 (Laragon › Menu › PHP › Version) |

### 5. Accounts (15 minutes)
The data still has the **demo accounts**, and their password (`password123`) is public on GitHub. Replace them now:
1. Double-click **EMO Tracker** on the desktop. Log in with `admin@emo.test` / `password123`.
2. **Accounts › Edit "EMO Administrator":** the EMO head's real name and email, and a new password that they type themselves.
3. **+ Add account** for each EMO member: name, email, role, and a temporary password. They change it after their first login (user menu › **Change password**).
4. **Deactivate** the 9 demo accounts: Maria Santos, Juan Dela Cruz, Anna Reyes, Mark Tan, Joy Garcia, Paolo Cruz, Liza Ramos, Ben Aquino, Carla Lim.
5. Log out, then log in with the new Administrator account.

### 6. Test (10 minutes)
- [ ] **Phone on the same Wi-Fi:** open the address from step 4 and log in. *(Doesn't open? See "If other devices can't connect" below.)*
- [ ] **On the laptop:** upload a small file to an event, download its PDF report, and export the Schedule.
- [ ] **Restart the laptop.** Wait a minute after Windows starts, then double-click the icon. It should open by itself.

### 7. Laptop settings (5 minutes)
- **Settings › System › Power:** when plugged in, the laptop never sleeps (the screen may turn off).
- **Control Panel › Power Options › Choose what closing the lid does:** when plugged in, **Do nothing**.
- **The clock** shows the correct Philippine time.

### 8. Backups (5 minutes)
- EMO Tracker saves a backup **every day** (the newest 14 are kept) in `C:\EMOTracker\backend\storage\app\private\backups`.
- **Save one now:** in Laragon, click **Terminal**, then:
  ```
  cd C:\EMOTracker\backend
  php artisan backup:run --name=after-install
  ```
- **A backup on the same laptop doesn't survive losing the laptop.** Either copy that folder to a USB drive every week, or have the backups saved to a Google Drive or OneDrive folder (`BACKUP_PATH` in `C:\EMOTracker\backend\.env`; the example there shows how).

## If other devices can't connect
Some Wi-Fi networks, often university ones, don't let devices reach each other. EMO Tracker still works on the server laptop itself. To let others in without buying anything:
1. On the server laptop: **Settings › Network & internet › Mobile hotspot:** turn it **On**, and turn off its power saving.
2. The other devices join that hotspot's Wi-Fi (its name and password are on the same page) and open **http://192.168.137.1:8000**.

A small Wi-Fi router in the office is the permanent fix.

## If something goes wrong
- **"EMO Tracker could not start":** restart the laptop. If it still happens, open Laragon and click Start All, then try the icon again.
- **Details for the team:** `C:\EMOTracker\backend\storage\logs\server.log` and `laravel.log`.
- **Backup plan for the pilot:** the MacBook with `start.command`, as before.

## Updating EMO Tracker later
1. On the Mac: `office/make-usb-copy.command` (without `--with-data`).
2. On the server laptop: copy the new **EMOTracker** folder onto `C:\` and choose **Replace**. The settings (`backend\.env`) and the data aren't in the copy, so they stay.
3. Run **Setup EMO Tracker.bat** again. It saves a backup, then updates the database. If it offers to load data, press **N**.

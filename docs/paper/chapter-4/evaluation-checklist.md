# Evaluation Checklist: Usability Test, SUS and ISO/IEC 25010

**Status (November 19, 2026):** the group revised the forms (November 16 to 19): the SUS is now the all-positive version, usability tasks 2, 4 and 5 use the "CAS Seminar", and four ISO statements are in plainer words. The printable versions are the `_FINAL` PDFs, and the scoring sheet matches them (including the new SUS formula). All 17 tasks were run again on fresh demo data and work. **The EMO answers the forms on November 20, 2026.** The Administrator and Officer use the MacBook (localhost, demo data); Staff uses a phone with the online version, which has a practice event and practice account for tasks 14 to 17 (tested on a phone-sized screen on November 19).

These files are the latest versions. If anyone changes a form's wording, change both the HTML file and the `_FINAL` PDF, and make the same change in `evaluation-scoring.xlsx` (task names, ISO statements, and the SUS formula if the SUS changes).

Files, all in this folder:

| File | What it is |
|---|---|
| `EMO Tracker Usability Test_FINAL.pdf` | Page 1: guide for the observers. Then, for each role, its task sheet and its closing questions page (7 pages) |
| `EMO Tracker System Usability Scale (SUS)_FINAL.pdf` | 10 positive statements (all-positive SUS), 1 page |
| `EMO Tracker ISO 25010 Questionnaire_FINAL.pdf` | 22 statements and 3 remarks questions, 3 pages |
| `evaluation-scoring.xlsx` | Type the answers here; it computes everything |
| `Program flow.pdf` | Sample file for usability task 9 (copied to the MacBook's Desktop on November 19) |

---

## 1. Before the visit

- [ ] The adviser (and the prof) approve the three forms. Make any wording change in both the form and `evaluation-scoring.xlsx`.
- [ ] Ask the adviser whether the evaluation date leaves enough time to write the results before the defense.
- [x] Put the November 14 update (the Schedule filters) on the online version, emo-tracker-one.vercel.app, so the EMO can try it before answering the questionnaires. Done November 14.
- [x] Prepare the online version for the Staff session: the practice account `anna.staff@emo.test` / `password123` is active again, and the practice event "Freshmen Orientation 2026" (December 3, typed location "Practice event (usability test)") has her 5 tasks, including "Print ID lanyards". Done November 19.
- [ ] The morning of the visit, open emo-tracker-one.vercel.app on the Staff phone. If every page says "Server Error", the database is off: power it on in Aiven, then log in as Anna to check her Home shows 2 open tasks. Log out again.
- [ ] Set a time with the EMO: 3 users (one Administrator, one Officer, one Staff), about 40 minutes each.
- [ ] Print for each user: their role's task sheet and closing questions page, the SUS and the ISO questionnaire, plus 2 spares of each. Print the observers' guide (page 1 of the usability test).
- [ ] Bring the MacBook and its charger, a phone with mobile data or Wi-Fi for the Staff user (online version), a stopwatch (any phone) and pens.

## 2. On the day: set up the MacBook (10 minutes, before the EMO arrives)

The Administrator and Officer sessions use the **demo data**, never the EMO's real schedule. The empty demo database (`emo_tracker_demo`) already exists. (The Staff session is on the phone with the online version; nothing to set up on the MacBook for it.)

1. Open `backend/.env` and change `DB_DATABASE=emo_tracker` to `DB_DATABASE=emo_tracker_demo`. Save.
2. In Terminal:
   ```
   cd ~/Projects/EMOTracker/backend
   php artisan config:show database.connections.mysql.database
   ```
   It must end with **emo_tracker_demo**. If it says `emo_tracker`, **stop**: step 1 didn't save.
3. Load the demo data (type `yes` if asked):
   ```
   php artisan migrate:fresh --seed
   ```
4. Double-click `start.command`. Log in as `admin@emo.test` / `password123` to check.
5. **Before the Administrator and before the Officer:** run step 3 again, so each starts with the same data.
6. **After the last user:** close the Terminal windows, change `.env` back to `DB_DATABASE=emo_tracker`, and run the step 2 command again. It must say `emo_tracker`.

## 3. Each session, in this order

1. Explain the consent note, and write the same Respondent No. and the date on all three forms.
2. **Usability test:** one proponent reads each task and keeps time; another writes. Don't help unless the user is stuck for 3 minutes (check "With help"). After each task, ask how easy it was, from 5 (very easy) to 1 (very difficult), and circle the number.
3. **SUS:** the user answers alone.
4. **ISO/IEC 25010 questionnaire:** the user answers alone.
5. Collect all three forms.

## 4. After the visit

- [ ] **Online cleanup:** tell Xyrone, so the practice event "Freshmen Orientation 2026" is deleted from the online version and `anna.staff@emo.test` is deactivated again.
- [ ] Type the answers into `evaluation-scoring.xlsx`: the Usability test, SUS, ISO ratings, Respondents and Comments tabs. Check every number against the paper forms.
- [ ] **Chapter 3:** write the evaluation method section (the "[ADD THE EVALUATION METHOD SECTION]" placeholder). Headings and facts: guide Section 6. Use the real date.
- [ ] **Chapter 4, Objective 4:** Table 4.6 (usability test), Table 4.7 and Figure 4.27 (ISO/IEC 25010), Table 4.8 (SUS), the comments, the problems found, the limits, and "Overall, Objective 4 was met" only if the results support it. Order and layouts: guide Section 5.4.
- [ ] **Figures 3.1 and G.2:** if the evaluation happens in November, Xyrone updates "pilot and evaluation (Oct 2026)" and adds the evaluation date to the Gantt chart.
- [ ] **Appendix B:** add the three blank forms (each HTML file has a Copy button). Add their names under Appendix B in the table of contents.
- [ ] **Appendix C:** the answered forms or their summary, with no names.
- [ ] **Chapter 5:** summary, conclusions and recommendations (guide Section 7).
- [ ] **Last:** table of contents, List of Figures, List of Tables, and the title page date.

## 5. Still waiting for the EMO's answer (not the forms)

- The number of main members and their positions: pp. 8, 11, 29–30, 36, 38–39, 53, 57, and the org chart (Figure G.1).
- How the pilot ran (the temporary online version) and where the backups go: pp. 21, 55.

# 🚩 कार्यकर्ता पोर्टल (Worker / Karyakarta Field Application)

हे एक स्वतंत्र (Standalone) React + Vite वेब अ‍ॅप्लिकेशन आहे जे विशेषतः ग्राऊंड-लेव्हल राजकीय कार्यकर्ते आणि फील्ड कर्मचाऱ्यांसाठी तयार केले गेले आहे. हे अ‍ॅप तुम्ही **Vercel, Netlify, cPanel, Apache/Nginx किंवा कोणत्याही Node/Web सर्व्हरवर थेट अपलोड करून चालवू शकता**.

---

## 🌟 वैशिष्ट्ये (Key Features)

1. **🗳️ EVM व्होटिंग प्रणाली (Interactive EVM Voting System):**
   - प्रत्यक्ष मतदान मशीनसारखा अनुभव, मतदानानंतर बीप व व्होट साऊंड (Audio feedback).
   - स्थानिक पातळीवर उमेदवारांचे मतदान प्रात्यक्षिक व मतदारांना प्रशिक्षण देण्याची सुविधा.

2. **📸 रिअल-टाइम सेल्फी आणि लोकेशन हजेरी (Live Attendance with Photo & GPS):**
   - वेबकॅम/कॅमेऱ्याद्वारे पंच-इन व पंच-आऊट (Punch In / Punch Out).
   - जीपीएस अक्षांश/रेखांश (GPS Coordinates) आणि अचूक भारतीय प्रमाणवेळ (IST Live Clock).

3. **📢 नागरिक तक्रार निवारण (Complaints & Grievance Tracker):**
   - प्रभाग व वॉर्डनिहाय समस्या नोंदणी (पाणी, रस्ते, वीज, कचरा इ.).
   - स्टेटस ट्रॅकिंग (Pending, In Progress, Resolved) आणि तक्रारींचे वर्गीकरण.

4. **📅 संपर्क आणि बैठका (Contacts & Meetings Management):**
   - आगामी बैठकांचे वेळापत्रक, २४ तास आधीचा ऑटोमॅटिक रिमाइंडर अलर्ट.
   - गुगल मीट (Google Meet) 1-क्लिक जॉइनिंग आणि लिंक शेअरिंग.

5. **📋 मतदार सर्व्हेक्षण (Voter Survey & Sentiment Analysis):**
   - मतदारांची मते, कल आणि समस्यांची डिजिटल नोंद.

6. **🗺️ ६-स्तरीय भौगोलिक रचना (6-Level Geographic Hierarchy):**
   - राज्य > जिल्हा > लोकसभा > विधानसभा > तालुका > गाव/वॉर्ड > पोलिंग स्टेशन.

7. **⚡ ऑफलाइन सिंक मॅनेजर (Offline Sync Support):**
   - नेटवर्क नसतानाही डेटा स्थानिक पातळीवर सुरक्षित राहतो व इंटरनेट आल्यावर सर्व्हरशी सिंक होतो.

8. **⚡ १-क्लिक डेमो लॉगिन (1-Click Demo Access):**
   - बॅकएंड सुरू नसला तरीही चाचणी घेण्यासाठी थेट लॉगिनची सुविधा.

---

## 🚀 स्थानिक पातळीवर कसे सुरू करावे? (Local Setup)

### १. डिपेंडन्सी इन्स्टॉल करा:
```bash
npm install
```

### २. डेव्हलपमेंट सर्व्हर सुरू करा:
```bash
npm run dev
```
सर्व्हर सुरू झाल्यावर `http://localhost:5173` वर उघडा.

### ३. प्रॉडक्शन बिल्ड तयार करा:
```bash
npm run build
```
यामुळे `dist/` फोल्डर तयार होईल.

---

## 🌐 Vercel वर थेट अपलोड व डिप्लॉय कसे करावे? (Deploy to Vercel)

या प्रोजेक्टमध्ये आधीच `vercel.json` समाविष्ट केलेले आहे, त्यामुळे Vercel वर 404 चा कोणताही एरर येत नाही.

### पर्याय १: GitHub द्वारे Vercel वर डिप्लॉय
1. ही फाईल्स तुमच्या GitHub रिपॉझिटरीमध्ये पुश करा.
2. [Vercel Dashboard](https://vercel.com) वर जाऊन **Add New Project** निवडा.
3. तुमची GitHub Repo निवडा.
4. Settings:
   - **Framework Preset:** Vite
   - **Build Command:** `npm run build`
   - **Output Directory:** `dist`
5. **Environment Variables** (पर्यायी):
   - Key: `VITE_API_BASE_URL`
   - Value: `https://your-api-domain.com/api`
6. **Deploy** वर क्लिक करा! अवघ्या १ मिनिटात अ‍ॅप लाईव्ह होईल.

### पर्याय २: Vercel CLI द्वारे
```bash
npm i -g vercel
vercel
```

---

## 📁 प्रोजेक्ट स्ट्रक्चर (Project Structure)

```
worker-portal-app/
├── public/                 # Static assets (favicons, icons)
├── src/
│   ├── api/                # Axios API client
│   ├── assets/             # Audio (vote_song.mp3, wrong_sound.mp3) & images
│   ├── components/         # Common UI components (BackButton, etc.)
│   ├── context/            # AuthContext (State & Token management)
│   ├── hooks/              # Custom React hooks (useAuth)
│   ├── pages/
│   │   ├── auth/           # WorkerLogin.jsx
│   │   └── worker_Karyakarta/
│   │       ├── components/ # KarkartaEvmVotingSystem.jsx
│   │       ├── tabs/       # All 10 worker modules/tabs
│   │       ├── offlineSyncManager.js
│   │       ├── translations.js
│   │       ├── worker.css
│   │       ├── workerApi.js
│   │       └── WorkerDashboard.jsx
│   ├── services/           # Authentication HTTP services
│   ├── utils/              # Token storage
│   ├── App.jsx             # Main Router
│   ├── index.css           # Global Devanagari & Inter fonts
│   └── main.jsx            # Entry point
├── .env.example
├── index.html
├── package.json
├── vercel.json             # Vercel SPA routing
└── vite.config.js
```

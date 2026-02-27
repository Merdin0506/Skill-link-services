1\. Introduction



This document provides complete instructions for setting up and running the SkillLink Desktop Application.



The desktop application is built using:



Electron.js



Node.js



HTML / CSS / JavaScript



The desktop application serves as a client interface that communicates with the SkillLink backend API.



2\. Development Environment Requirements



Before running the desktop module, ensure the following software is installed:



2.1 Required Software

Software	Purpose

Node.js (LTS)	JavaScript runtime

npm	Package manager (comes with Node.js)

Git	Version control

VS Code (recommended)	Code editor

2.2 Verify Installation



Open Command Prompt and run:



node -v

npm -v

git --version



If version numbers appear, installation is successful.



3\. Project Structure



The desktop module is located inside the main repository:



Skill-link-services/

│

├── DESKTOP/

│   ├── main.js

│   ├── index.html

│   ├── package.json

│   ├── package-lock.json

│   ├── .gitignore

│   └── node\_modules/

│

└── Documentation/

File Descriptions



main.js – Electron main process (creates application window)



index.html – User interface layout



package.json – Project configuration and scripts



.gitignore – Files excluded from Git tracking



4\. Installation Procedure

Step 1: Clone Repository (if not yet cloned)

git clone https://github.com/your-team/Skill-link-services.git

cd Skill-link-services

Step 2: Navigate to Desktop Directory

cd DESKTOP

Step 3: Install Dependencies



Run:



npm install



This installs Electron and required packages inside the node\_modules folder.



⚠ Do NOT push node\_modules to GitHub.



5\. Running the Desktop Application



After installation, run:



npm start



If successful, the Electron window will open displaying:



SkillLink Desktop App Running

6\. Application Architecture



The desktop application follows a client-server architecture:



Desktop (Electron)

&nbsp;       ↓

Backend API (CodeIgniter)

&nbsp;       ↓

MySQL Database



The desktop module does not directly connect to the database.

All data operations are handled via API requests.



7\. Configuration Notes



The main entry point is defined in package.json:



"main": "main.js",

"scripts": {

&nbsp; "start": "electron ."

}



The Electron window is created inside main.js using:



app



BrowserWindow



8\. Git Configuration



The following files and folders must not be pushed:



node\_modules/

dist/

out/

.env



Ensure DESKTOP/.gitignore contains:



node\_modules/

dist/

out/

.env


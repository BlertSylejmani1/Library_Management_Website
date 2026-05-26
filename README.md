# Library_Management_Website
Ky është një aplikacion web për menaxhimin e librarisë i zhvilluar me HTML, CSS dhe JavaScript për frontend dhe për backend me PHP dhe Mysql.
Qëllimi është të ofrojë një sistem funksional për menaxhimin e librave, përdoruesve dhe huazimeve.
Projekti është i ndarë në dy faza:

Faza I – Implementimi bazë pa databazë

Faza II – Integrimi me databazë dhe funksionalitete të avancuara

# Funksionalitetet kryesore: 
- Login/Logout me të dhëna statike 

- Role të përdoruesve (Admin / User)

- Menaxhim i librave (simulim)

- Shfaqje e të dhënave përmes arrays

# Koncepte të përdorura:
- Variabla, funksione, kushte dhe cikle

- Arrays (indexed, associative, multidimensional)

- Programimi i orientuar në objekte (OOP)

- Validim me RegEx (email, telefon)

- Sessions dhe Cookies

# Ekzekutimi i Projektit 
1. Vendoseni projektin në folderin htdocs te XAMPP(paraprakisht të jetë i instaluar)

2. Startoni Apache Serverin

3. Hapeni në browser http://localhost/Library_Management_Website/login.php

4. Çasjen në sistem mund të e bëni si Admin me kredencialet: admin@library.com psw:admin123 si dhe si Student me kredencialet : student@library.com psw:student123

# Faza e dyte e projektit
Projekti Library Management System në Fazën II është zgjeruar me databazë MySQL, CRUD real, siguri bazë, AJAX dhe Web API. Sistemi mundëson menaxhimin e librave, përdoruesve, huazimeve dhe kërkesave për libra.

Janë përdorur PHP, MySQL, PDO, HTML, CSS, JavaScript dhe AJAX. Databaza gjendet në database/library_management.sql dhe përmban tabelat users, books, loans dhe book_requests.

Projekti përdor prepared statements për mbrojtje nga SQL Injection, htmlspecialchars() për XSS, validim server-side, CSRF token dhe password_hash() / password_verify() për fjalëkalime.

AJAX përdoret për fshirjen e librave, kthimin/rinovimin e huazimeve dhe kërkesat për libra. Gjithashtu është integruar Open Library API për kërkim librash sipas titullit ose ISBN-së.

Për ekzekutim: importo database/library_management.sql, starto Apache/MySQL në XAMPP dhe hape projektin në browser. Llogaritë testuese janë admin@library.com / admin123 dhe student@library.com / student123.

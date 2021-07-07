# LBAW Class Assignments and Projects

**2019/2020** - 3rd Year, 2nd Semester

**Course:** Laboratório de Bases de Dados e Aplicações Web ([LBAW](https://sigarra.up.pt/feup/pt/ucurr_geral.ficha_uc_view?pv_ocorrencia_id=436452)) | Databases and Web Development Lab

**Projects developed by:** David Silva ([daviddias99](https://github.com/daviddias99)), Eduardo Ribeiro ([EduRibeiro00](https://github.com/EduRibeiro00)), Luís Cunha ([luispcunha](https://github.com/luispcunha)) and Manuel Coutinho ([ManelCoutinho](https://github.com/ManelCoutinho))

---

**Project: NewsLab**

* Website/platform where users write and share news and opinions about different subjects, enabling people to interact with the articles by rating or commenting them;
* Did various tasks regarding website planning and specification, from developing frontend page mockups to creating data models and schemas for the backend database structure;
* Adopted an Agile methodology to develop this project, working in iterations and selecting user stories to implement for each one;
* Developed a REST API to allow the frontend to connect with backend services;
* Languages/technologies used: **PHP, Laravel, Javascript, HTML, CSS, PostgreSQL, Docker.**

**Grade**: 19.4 / 20

---

**Disclaimer** - This repository was used for educational purposes and I do not take any responsibility for anything related to its content. You are free to use any code or algorithm you find, but do so at your own risk.

---

# NewsLab (Collaborative News)

## Project

### Team

* David Luís Dias da Silva, up201705373@fe.up.pt
* Eduardo Carreira Ribeiro, up201705421@fe.up.pt
* Luís Pedro Pereira Lopes Mascarenhas Cunha, up201706736@fe.up.pt
* Manuel Monge dos Santos Pereira Coutinho, up201704211@fe.up.pt

### Artefacts

* User Requirements specification
  * [A1: Project presentation](./wiki/a1.md)
  * [A2: Actors and User stories](./wiki/a2.md)
  * [A3: User Interfaces Prototype](./wiki/a3.md)
* Database specification
  * [A4: Conceptual Data Model](./wiki/a4.md)
  * [A5: Relational schema, validation and schema refinement](./wiki/a5.md)
  * [A6: Integrity constraints. Indexes, triggers, user functions, transactions and database populated with data](./wiki/a6.md)
* Architecture specification and Prototype
  * [A7: High-level architecture. Privileges. Web resources specification](./wiki/a7.md)
  * [A8: Vertical Prototype](./wiki/a8.md)
* Product and Presentation
  * [A9: Product](./wiki/a9.md)
  * [A10: Presentation and discussion](./wiki/a10.md)

### Instalation and usage

* [URL of the product](http://lbaw2022.lbaw-prod.fe.up.pt/)
  
* Running the image:

```
docker run -d -it -p 8080:80 -v $PWD/:/var/www/html lbaw2022/lbaw2022:latest -e DB_USERNAME="lbaw2022" -e DB_PASSWORD="DP580136" 
docker-compose up
```

  
* User credentials:

| Type          | Username  | Password |
| ------------- | --------- | -------- |
| regular user  | eduvidas@uporto.edu    | Asdqwe123 |
| banned user   | mcgun@outlook.com    | Asdqwe123 |
| administrator | metadias@gmail.com    | Asdqwe123 |

***
GROUP2022, 18/02/2020


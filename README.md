# NewsLab (Collaborative News)

## Project

### Team

* David Luís Dias da Silva, up201705373@fe.up.pt
* Eduardo Carreira Ribeiro, up201705421@fe.up.pt
* Luís Pedro Pereira Lopes Mascarenhas Cunha, up201706736@fe.up.pt
* Manuel Monge dos Santos Pereira Coutinho, up201704211@fe.up.pt

### Artefacts

* User Requirements specification
  * [A1: Project presentation](../../wikis/a1)
  * [A2: Actors and User stories](../../wikis/a2)
  * [A3: User Interfaces Prototype](../../wikis/a3)
* Database specification
  * [A4: Conceptual Data Model](../../wikis/a4)
  * [A5: Relational schema, validation and schema refinement](../../wikis/a5)
  * [A6: Integrity constraints. Indexes, triggers, user functions, transactions and database populated with data](../../wikis/a6)
* Architecture specification and Prototype
  * [A7: High-level architecture. Privileges. Web resources specification](../../wikis/a7)
  * [A8: Vertical Prototype](../../wikis/a8)
* Product and Presentation
  * [A9: Product](../../wikis/a9)
  * [A10: Presentation and discussion](../../wikis/a10)

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


# DATABASE CREATION
```
DROP TABLE IF EXISTS `Friends`;
CREATE TABLE `Friends` (
  `username_sender` varchar(255) NOT NULL,
  `username_reciever` varchar(255) NOT NULL,
  `accept_date` date DEFAULT NULL,
  `requestId` varchar(255) NOT NULL,
  `declined` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`requestId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `Game`;
CREATE TABLE `Game` (
  `username` varchar(255) NOT NULL,
  `gameId` int NOT NULL,
  `wishlist` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`username`,`gameId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


DROP TABLE IF EXISTS `User`;
CREATE TABLE `User` (
  `token` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `confirmed` bit(1) NOT NULL DEFAULT b'0',
  `wallet` decimal(15,2) NOT NULL DEFAULT '0.00',
  `picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

# Local Environment
> Using Docker for our local environment

## Requirements

1. Having [Docker installed](https://www.docker.com/products/docker-desktop) (you will need to create a Hub account)
2. Having [Git installed](https://git-scm.com/downloads)

## Installation

1. Clone this repository into your projects folder using the `git clone` command.

## Instructions

1. After cloning the project, open your terminal and access the root folder using the `cd /path/to/the/folder` command.
2. To start the local environment, execute the command `docker-compose up -d` in your terminal.

**Note:** The first time you run this command it will take some time because it will download all the required images from the Hub.

At this point, if you execute the command `docker-compose ps` you should see a total of 4 containers running:

```
       Name                     Command               State                 Ports              
-----------------------------------------------------------------------------------------------
pw_local_env-admin   entrypoint.sh docker-php-e ...   Up      0.0.0.0:8080->8080/tcp           
pw_local_env-db      docker-entrypoint.sh mysqld      Up      0.0.0.0:3330->3306/tcp, 33060/tcp
pw_local_env-nginx   /docker-entrypoint.sh ngin ...   Up      0.0.0.0:8030->80/tcp             
pw_local_env-php     docker-php-entrypoint php-fpm    Up      9000/tcp, 0.0.0.0:9030->9001/tcp
```

At this point, you should be able to access the application by visiting the following address in your browser [http://localhost:8030/](http://localhost:8030/).

### Database

There are multiple ways to access the database inside the docker container. In this case we are going to cover two options:

1. Manually accessing the container
2. Using the adminer image

#### Manually

In order to manually access the database, we need the name of the database container. Use `docker-compose ps`. The name should be something like `pw_local_env-db`.

Now, we are going to ssh into the container using the command `docker exec -it container_id bash`. At this point, you should be able to notice that the terminal prompt has changed because now you are inside of the container.

To access the database, execute the command `mysql -u root -p`. (The password is the one specified in the .env file in the **MYSQL_ROOT_PASSWORD** field.)

#### Adminer image

To access to the admin page, visit the URL [http://localhost:8080/](http://localhost:8080/) in your browser.

The host should be **db** (the name of the service used in the docker-compose file).

The user should be **root** and the password is the one specified in the .env file in the **MYSQL_ROOT_PASSWORD** field.

### Shared directories

The line
```
volumes:
    - .:/app
```

in

```
app:
container_name: pw_local_env-php
build:
    context: .
    dockerfile: Dockerfile
restart: unless-stopped
ports:
    - "9030:9001"
volumes:
    - .:/app
depends_on:
    - db
```

is telling docker to share the `/app` directory inside docker with the `.` (current) directory on the host machine (your computer). If you change the contents of the php file `public/index.php` you will see the changes when accessing your web at [http://localhost:8080/](http://localhost:8080/).

## QA

1. How to list all the running containers

Use the `docker ps` command. Use the -a flag to list also the stopped ones.

2. How to list all the docker images that I have installed.

Run the command `docker images`

3. How to remove all the images that are no longer used

Run the command `docker image prune`

4. How to check the logs of a specific container

Run the command `docker ps` and copy the id of the container that you want to debug.

Now, run the command `docker logs --follow container_id`.

5. How to _ssh_ into a specific container

Run the command `docker ps` and copy the id of the container that you want to debug.

Now, run the command `docker exec -it container_id bash`.

**Note:** If you are using the alpine version of image, you need to use _ash_ instead of _bash_.

6. Where can I find more docker images to use?

You can check the [Docker Hub](https://hub.docker.com/).




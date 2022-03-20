# LeChatelet
MSPR administrer et sécuriser

#INSTALLATION# 


Après installation , penser à faire un composer install, créer une copie du .env et nommez la .env.local et remplacer la ligne DATABASE_URL par cette ligne : 
DATABASE_URL="mysql://root@127.0.0.1:3306/leChatelet?serverVersion=mariadb-10.4.11"

Par la suite, éxécuter cette commande dans un terminal :
php bin/console doctrine:database:create


Cela génèrera la base de donnée, puis appliquer la migration en exécutant : 
php bin/console doctrine:migrations:migrate


Ne pas oublier que ce sera la version de DEV, une branche sera peut-être mise à disposition pour la version de Production

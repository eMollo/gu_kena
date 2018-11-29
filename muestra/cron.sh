#!/bin/bash

#script actualizacion json

#cd /home/gukena/toba_2.7.2
cd /home/pablo/Proyectos/toba/toba2.7composer/vendor/siu-toba/framework
. entorno.sh
cd proyectos/gu_kena/www
toba item ejecutar -p gu_kena -t 10000050
#/usr/bin/rsync -azh --stats --delete /home/gukena/toba_2.7.2/proyectos/gu_kena/www/resultados_json/e20180822/ andrea@170.210.81.203:/home/andrea/web/resultadosGukena/e20180822/

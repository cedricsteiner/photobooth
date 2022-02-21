#/bin/bash
now=$(date)
#sleep 3
cd images/tmp
gphoto2 --capture-image-and-download --set-config capturetarget=1 
echo "capture $now"

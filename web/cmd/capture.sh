#/bin/bash
now=$(date)
#sleep 3
cd images/tmp
gphoto2 --capture-image-and-download
echo "capture $now"

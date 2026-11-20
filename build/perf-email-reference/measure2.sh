cp -a /srv /w && cd /w && sed -i "s#/srv#/w#" config/config.php
mkdir -p /out2
php -S localhost:8080 >/dev/null 2>&1 &
sleep 1
for i in $(seq 1 15); do for v in unfixed fixed; do
  cp /bin2/Manager.$v.php lib/private/User/Manager.php
  bash /bin2/scen.sh
  find data/__profiler -mindepth 1 -delete 2>/dev/null
  bash /bin2/scen.sh
  php occ profiler:export > /out2/$v-$i.json 2>/dev/null
done; done
php /bin2/stats.php /out2

#!/bin/bash
set -e

: ${CLUSTER:=ceph}
: ${RGW_NAME:=$(hostname -s)}
: ${MON_NAME:=$(hostname -s)}
: ${RGW_CIVETWEB_PORT:=80}
: ${OSD_SIZE:=100}

: ${KEYSTONE_ADMIN_TOKEN:=admin}
: ${KEYSTONE_ADMIN_PORT:=35357}
: ${KEYSTONE_PUBLIC_PORT:=5001}

: ${KEYSTONE_SERVICE:=${CLUSTER}}
: ${KEYSTONE_ENDPOINT_REGION:=region}

: ${KEYSTONE_ADMIN_USER:=admin}
: ${KEYSTONE_ADMIN_TENANT:=admin}
: ${KEYSTONE_ADMIN_PASS:=admin}

ip_address=$(head -n1 /etc/hosts | cut -d"	" -f1)
: ${MON_IP:=${ip_address}}
subnet=$(ip route | grep "src ${ip_address}" | cut -d" " -f1)
: ${CEPH_NETWORK:=${subnet}}

#######
# MON #
#######

if [ ! -n "$CEPH_NETWORK" ]; then
   echo "ERROR- CEPH_NETWORK must be defined as the name of the network for the OSDs"
   exit 1
fi

if [ ! -n "$MON_IP" ]; then
   echo "ERROR- MON_IP must be defined as the IP address of the monitor"
   exit 1
fi

# bootstrap MON
if [ ! -e /etc/ceph/ceph.conf ]; then
   fsid=$(uuidgen)
   cat <<ENDHERE >/etc/ceph/${CLUSTER}.conf
[global]
fsid = $fsid
mon initial members = ${MON_NAME}
mon host = ${MON_IP}
auth cluster required = cephx
auth service required = cephx
auth client required = cephx
osd crush chooseleaf type = 0
osd journal size = 100
osd pool default pg num = 8
osd pool default pgp num = 8
osd pool default size = 1
public network = ${CEPH_NETWORK}
cluster network = ${CEPH_NETWORK}
debug ms = 1

[mon]
debug mon = 20
debug paxos = 20
debug auth = 20

[osd]
debug osd = 20
debug filestore = 20
debug journal = 20
debug monc = 20

[mds]
debug mds = 20
debug mds balancer = 20
debug mds log = 20
debug mds migrator = 20

[client.radosgw.gateway]
rgw keystone url = http://${MON_IP}:${KEYSTONE_ADMIN_PORT}
rgw keystone admin token = ${KEYSTONE_ADMIN_TOKEN}
rgw keystone accepted roles = _member_
ENDHERE

   # Generate administrator key
   ceph-authtool /etc/ceph/${CLUSTER}.client.admin.keyring --create-keyring --gen-key -n client.admin --set-uid=0 --cap mon 'allow *' --cap osd 'allow *' --cap mds 'allow'

   # Generate the mon. key
   ceph-authtool /etc/ceph/${CLUSTER}.mon.keyring --create-keyring --gen-key -n mon. --cap mon 'allow *'

   # Generate initial monitor map
   monmaptool --create --add ${MON_NAME} ${MON_IP} --fsid ${fsid} /etc/ceph/monmap
fi

# If we don't have a monitor keyring, this is a new monitor
if [ ! -e /var/lib/ceph/mon/${CLUSTER}-${MON_NAME}/keyring ]; then

   if [ ! -e /etc/ceph/${CLUSTER}.client.admin.keyring ]; then
      echo "ERROR- /etc/ceph/${CLUSTER}.client.admin.keyring must exist; get it from your existing mon"
      exit 2
   fi

   if [ ! -e /etc/ceph/${CLUSTER}.mon.keyring ]; then
      echo "ERROR- /etc/ceph/${CLUSTER}.mon.keyring must exist.  You can extract it from your current monitor by running 'ceph auth get mon. -o /tmp/${CLUSTER}.mon.keyring'"
      exit 3
   fi

   if [ ! -e /etc/ceph/monmap ]; then
      echo "ERROR- /etc/ceph/monmap must exist.  You can extract it from your current monitor by running 'ceph mon getmap -o /tmp/monmap'"
      exit 4
   fi

   # Import the client.admin keyring and the monitor keyring into a new, temporary one
   ceph-authtool /tmp/${CLUSTER}.mon.keyring --create-keyring --import-keyring /etc/ceph/${CLUSTER}.client.admin.keyring
   ceph-authtool /tmp/${CLUSTER}.mon.keyring --import-keyring /etc/ceph/${CLUSTER}.mon.keyring

   # Make the monitor directory
   mkdir -p /var/lib/ceph/mon/${CLUSTER}-${MON_NAME}

   # Prepare the monitor daemon's directory with the map and keyring
   ceph-mon --mkfs -i ${MON_NAME} --monmap /etc/ceph/monmap --keyring /tmp/${CLUSTER}.mon.keyring

   # Clean up the temporary key
   rm /tmp/${CLUSTER}.mon.keyring
fi

# start MON
ceph-mon -i ${MON_NAME} --public-addr ${MON_IP}:6789

# change replica size
ceph osd pool set rbd size 1


#######
# OSD #
#######

if [ ! -e /var/lib/ceph/osd/${CLUSTER}-0/keyring ]; then
  # bootstrap OSD
  mkdir -p /var/lib/ceph/osd/${CLUSTER}-0
  # skip btrfs HACK if btrfs is already in place
  if [ "$(stat -f /var/lib/ceph/osd/${CLUSTER}-0 2>/dev/null | grep btrfs | wc -l)" == "0"  ]; then
    # HACK create btrfs loopback device
    echo "creating osd storage image"
    dd if=/dev/zero of=/tmp/osddata bs=1M count=${OSD_SIZE}
    mkfs.btrfs /tmp/osddata
    echo "mounting via loopback"
    mount -o loop /tmp/osddata /var/lib/ceph/osd/${CLUSTER}-0
    echo "now mounted:"
    mount
    # end HACK
  fi
  echo "creating osd"
  ceph osd create
  echo "creating osd filesystem"
  ceph-osd -i 0 --mkfs
  echo "creating osd keyring"
  ceph auth get-or-create osd.0 osd 'allow *' mon 'allow profile osd' -o /var/lib/ceph/osd/${CLUSTER}-0/keyring
  echo "configuring osd crush"
  ceph osd crush add 0 1 root=default host=$(hostname -s)
  echo "adding osd keyring"
  ceph-osd -i 0 -k /var/lib/ceph/osd/${CLUSTER}-0/keyring
fi

# start OSD
echo "starting osd"
ceph-osd --cluster=${CLUSTER} -i 0

#sleep 10

#######
# MDS #
#######

if [ ! -e /var/lib/ceph/mds/${CLUSTER}-0/keyring ]; then
  # create ceph filesystem
  echo "creating osd pool"
  ceph osd pool create cephfs_data 8
  echo "creating osd pool metadata"
  ceph osd pool create cephfs_metadata 8
  echo "creating cephfs"
  ceph fs new cephfs cephfs_metadata cephfs_data

  # bootstrap MDS
  mkdir -p /var/lib/ceph/mds/${CLUSTER}-0
  echo "creating mds auth"
  ceph auth get-or-create mds.0 mds 'allow' osd 'allow *' mon 'allow profile mds' > /var/lib/ceph/mds/${CLUSTER}-0/keyring
fi

# start MDS
echo "starting mds"
ceph-mds --cluster=${CLUSTER} -i 0

#sleep 10


#######
# RGW #
#######

if [ ! -e /var/lib/ceph/radosgw/${RGW_NAME}/keyring ]; then
  # bootstrap RGW
  mkdir -p /var/lib/ceph/radosgw/${RGW_NAME}
  echo "creating rgw auth"
  ceph auth get-or-create client.radosgw.gateway osd 'allow rwx' mon 'allow rw' -o /var/lib/ceph/radosgw/${RGW_NAME}/keyring
fi

# start RGW
echo "starting rgw"
radosgw -c /etc/ceph/ceph.conf -n client.radosgw.gateway -k /var/lib/ceph/radosgw/${RGW_NAME}/keyring --rgw-socket-path="" --rgw-frontends="civetweb port=${RGW_CIVETWEB_PORT}"


#######
# API #
#######

# start ceph-rest-api
echo "starting rest api"
ceph-rest-api -n client.admin &

############
# Keystone #
############

if [ ! -e /etc/keystone/${CLUSTER}.conf ]; then
  cat <<ENDHERE > /etc/keystone/${CLUSTER}.conf
[DEFAULT]
admin_token=${KEYSTONE_ADMIN_TOKEN}
admin_port=${KEYSTONE_ADMIN_PORT}
public_port=${KEYSTONE_PUBLIC_PORT}

[database]
connection = sqlite:////var/lib/keystone/keystone.db
ENDHERE

  # start Keystone
  echo "starting keystone"
  keystone-all --config-file /etc/keystone/${CLUSTER}.conf &

  # wait until up
  while ! nc ${MON_IP} ${KEYSTONE_ADMIN_PORT} </dev/null; do
    sleep 1
  done

  export OS_SERVICE_TOKEN=${KEYSTONE_ADMIN_TOKEN}
  export OS_SERVICE_ENDPOINT=http://${MON_IP}:${KEYSTONE_ADMIN_PORT}/v2.0

  echo "creating keystone service ${KEYSTONE_SERVICE}"
  keystone service-create --name ${KEYSTONE_SERVICE} --type object-store
  echo "creating keystone endpoint ${KEYSTONE_SERVICE}"
  keystone endpoint-create --service ${KEYSTONE_SERVICE} \
    --region ${KEYSTONE_ENDPOINT_REGION} \
    --publicurl http://${MON_IP}:${RGW_CIVETWEB_PORT}/swift/v1 \
    --internalurl http://${MON_IP}:${RGW_CIVETWEB_PORT}/swift/v1 \
    --adminurl http://${MON_IP}:${RGW_CIVETWEB_PORT}/swift/v1

  echo "creating keystone user ${KEYSTONE_ADMIN_USER}"
  keystone user-create --name=${KEYSTONE_ADMIN_USER} --pass=${KEYSTONE_ADMIN_PASS} --email=dev@null.com
  echo "creating keystone tenant ${KEYSTONE_ADMIN_TENANT}"
  keystone tenant-create --name=${KEYSTONE_ADMIN_TENANT} --description=admin
  echo "adding keystone role _member_"
  keystone user-role-add --user=${KEYSTONE_ADMIN_USER} --tenant=${KEYSTONE_ADMIN_TENANT} --role=_member_

  echo "creating keystone role admin"
  keystone role-create --name=admin
  echo "adding keystone role admin"
  keystone user-role-add --user=${KEYSTONE_ADMIN_USER} --tenant=${KEYSTONE_ADMIN_TENANT} --role=admin
fi


#########
# WATCH #
#########

echo "watching ceph"
exec ceph -w

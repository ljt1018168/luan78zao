#!/bin/bash

#显示硬盘|大小 
Disk=$(fdisk -l |grep 'Disk' |awk -F , '{print $1}' | sed 's/Disk identifier.*//g' | sed '/^$/d')
echo -e "Amount Of Disks:\n${Disk}"


#!/bin/bash
##磁盘数量
Disk=$( fdisk -l |grep 'Disk' |grep 'sd' |awk -F , '{printf "%s",substr($1,10,4)}')
echo -e "${Disk}" >>/1



#!/bin/bash
##磁盘数量
Disk=$( fdisk -l |grep 'Disk' |grep 'sd' |awk -F , '{print "%s",substr($1,13,1)}')
var=${Disk: -1:1}
echo blacklist{ >> /etc/multipath.conf
echo \          devnode \"^sd[a-${var}]$\" >> /etc/multipath.conf
echo \          devnode \"^sd[a-${var}][1-9]$\"} >> /etc/multipath.conf
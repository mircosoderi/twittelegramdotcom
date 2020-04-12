#!/bin/bash

# Copyright 2020 Mirco Soderi
# 
# Permission is hereby granted, free of charge, to any person obtaining 
# a copy of this software and associated documentation files (the "Software"), 
# to deal in the Software without restriction, including without limitation 
# the rights to use, copy, modify, merge, publish, distribute, sublicense, 
# and/or sell copies of the Software, and to permit persons to whom the 
# Software is furnished to do so, subject to the following conditions:
# 
# The above copyright notice and this permission notice shall be included in
# all copies or substantial portions of the Software.
# 
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
# FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER 
# DEALINGS IN THE SOFTWARE.

if [ -f /home/usrmgr/addusers.todo ]; then

/usr/bin/sed -i '/^ttusr/!d' /home/usrmgr/addusers.todo

/usr/bin/mv /home/usrmgr/addusers.todo /home/usrmgr/addusers.txt

/usr/sbin/newusers /home/usrmgr/addusers.txt 

if [ ${?} -ne 0 ] ; then
   /usr/bin/cat /home/usrmgr/addusers.txt >> /home/usrmgr/addusers.todo
   exit 1
fi

/usr/bin/mv /home/usrmgr/addusers.txt /path/to/usrmgthst/addusers_$(date +%F_%R).txt

fi

if [ -f /home/usrmgr/delusers.todo ]; then

/usr/bin/sed -i '/^ttusr/!d' /home/usrmgr/delusers.todo

/usr/bin/mv /home/usrmgr/delusers.todo /home/usrmgr/delusers.txt

if [ ${?} -ne 0 ] ; then
   exit 1
fi

for user in $(< /home/usrmgr/delusers.txt)
do
/usr/sbin/userdel -r $user
if [ ${?} -ne 0 ] ; then
   /usr/bin/echo $user >> /home/usrmgr/delusers.todo
else
   /usr/bin/echo $user >> /home/usrmgr/deletedusers.tmp
fi
done
/usr/bin/rm -f /home/usrmgr/delusers.txt

/usr/bin/mv /home/usrmgr/deletedusers.tmp /path/to/usrmgthst/delusers_$(date +%F_%R).txt

fi

exit 0

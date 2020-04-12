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



for ttusr in $(/usr/bin/cat /etc/passwd | /usr/bin/grep "^ttusr" | /usr/bin/sed 's/:.*//'); do 
	if [ "$(/usr/bin/diff -N /path/to/ttenv/version.txt /home/$ttusr/ttenv/version.txt)" != "" ] 
	then
		/usr/bin/rm -Rf /home/$ttusr/ttenv
		/usr/bin/cp -r /path/to/ttenv /home/$ttusr
		/usr/bin/mkdir -p /home/$ttusr/ttenv/tmp
		/usr/bin/mkdir -p /home/$ttusr/ttenv/tmp/media
		/usr/bin/mkdir -p /home/$ttusr/ttenv/tmp/chunks
		if [ ! -f /home/$ttusr/ttctb ] || [ "$( /usr/bin/grep -L /home/$ttusr/ttenv/dowrite.sh /home/$ttusr/ttctb )" != "" ]; then
			/usr/bin/echo "* * * * * /home/$ttusr/ttenv/dowrite.sh" >> /home/$ttusr/ttctb
			/usr/bin/crontab -u $ttusr /home/$ttusr/ttctb
		fi
		/usr/bin/chown -R $ttusr:twittelegram /home/$ttusr/*
	fi
done

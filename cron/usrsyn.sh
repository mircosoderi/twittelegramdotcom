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
	if [ "$(/usr/bin/diff -N /path/to/ttenv/version.txt /home/$ttusr/ttenv/version.txt)" == "" ]; then
		for ttusrjob in $(/usr/bin/crontab -l -u $ttusr); do
			if [[ $ttusrjob == https://twitter.com/* ]]; then
				for usrfile in $(/usr/bin/grep -lr ${ttusrjob#"https://twitter.com"} /path/to/twitter/users/folder | awk -F/ '{ print $NF }'); do
					if [ ! -f /home/$ttusr/ttenv/users/twitter/$usrfile ] ; then
						/usr/bin/echo "Adding /home/$ttusr/ttenv/users/twitter/$usrfile"
						/usr/bin/cp /path/to/twitter/users/folder/$usrfile /home/$ttusr/ttenv/users/twitter/$usrfile
						/usr/bin/chmod +w /home/$ttusr/ttenv/users/twitter/$usrfile
						/usr/bin/chown $ttusr:twittelegram /home/$ttusr/ttenv/users/twitter/$usrfile
					fi
					if [ "$(/usr/bin/diff -N /home/$ttusr/ttenv/users/twitter/$usrfile /path/to/twitter/users/folder/$usrfile)" != "" ]; then	
						/usr/bin/echo "Updating /path/to/twitter/users/folder/$usrfile"
						/usr/bin/php /path/to/usrsyn.php /home/$ttusr/ttenv/users/twitter/$usrfile /path/to/twitter/users/folder/$usrfile
						/usr/bin/echo "Copying back to /home/$ttusr/ttenv/users/twitter/$usrfile"
						yes | /usr/bin/cp -rf /path/to/twitter/users/folder/$usrfile /home/$ttusr/ttenv/users/twitter/$usrfile		
						/usr/bin/chmod +w /home/$ttusr/ttenv/users/twitter/$usrfile
						/usr/bin/chown $ttusr:twittelegram /home/$ttusr/ttenv/users/twitter/$usrfile
					fi
				done
			fi
		done

		if [ ! -f /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json ] ; then
			/usr/bin/echo "Adding /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json"
	        /usr/bin/cp /path/to/telegram/users/folder/${ttusr:5}.json /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
			/usr/bin/chmod +w /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
			/usr/bin/chown $ttusr:twittelegram /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
        fi
        if [ "$(/usr/bin/diff -N /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json /path/to/telegram/users/folder/${ttusr:5}.json)" != "" ]; then
			/usr/bin/echo "Updating /path/to/telegram/users/folder/${ttusr:5}.json"
            /usr/bin/php /path/to/usrsyn.php /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json /path/to/telegram/users/folder/${ttusr:5}.json
			/usr/bin/echo "Copying back to /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json"
            yes | /usr/bin/cp -rf /path/to/telegram/users/folder/${ttusr:5}.json /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
			/usr/bin/chmod +w /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
			/usr/bin/chown $ttusr:twittelegram /home/$ttusr/ttenv/users/telegram/${ttusr:5}.json
        fi

		if [ "$( /usr/bin/pgrep dowrite -u $ttusr )" == ""  ]; then
			/usr/bin/echo "Generating .twurlrc.new"
			/usr/bin/php /path/to/twurl.php ${ttusr:5}
			if [ -f /home/$ttusr/.twurlrc.new ] && [ "$(/usr/bin/diff -N /home/$ttusr/.twurlrc /home/$ttusr/.twurlrc.new )" != "" ]; then
				yes | /usr/bin/cp -rf /home/$ttusr/.twurlrc /home/$ttusr/.twurlrc.bkp
				yes | /usr/bin/cp -rf /home/$ttusr/.twurlrc.new /home/$ttusr/.twurlrc
				/usr/bin/chmod +w /home/$ttusr/.twurlrc
				/usr/bin/chown $ttusr:twittelegram /home/$ttusr/.twurlrc				
			fi
		fi

		ttusr=${ttusr:5}
		for crow in $( /usr/bin/cat /home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json | /usr/bin/jq -r '.chats[] | @base64'); do
			_cjq() {
			        /usr/bin/echo ${crow} | /usr/bin/base64 --decode | /usr/bin/jq -r ${1}
			}
			for usrfilename in $( _cjq '.links'  | /usr/bin/sed "s/null/{}/g" | /usr/bin/jq 'keys[]' ); do
				usrfile=`/usr/bin/echo $usrfilename | /usr/bin/sed 's/.\(.*\)/\1/' | /usr/bin/sed 's/\(.*\)./\1/'`
				usrfile="${usrfile}.json"
				if [ ! -f /home/ttusr$ttusr/ttenv/users/twitter/$usrfile ] ; then
					/usr/bin/echo "Adding"
					/usr/bin/cp /path/to/twitter/users/folder/$usrfile /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
					/usr/bin/chmod +w /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
					/usr/bin/chown ttusr$ttusr:twittelegram /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
				fi
				if [ "$(/usr/bin/diff -N /home/ttusr$ttusr/ttenv/users/twitter/$usrfile /path/to/twitter/users/folder/$usrfile)" != "" ]; then
					/usr/bin/echo "Updating /path/to/twitter/users/folder/$usrfile"
					/usr/bin/php /path/to/usrsyn.php /home/ttusr$ttusr/ttenv/users/twitter/$usrfile /path/to/twitter/users/folder/$usrfile
					/usr/bin/echo "Copying back to /home/ttusr$ttusr/ttenv/users/twitter/$usrfile"
					yes | /usr/bin/cp -rf /path/to/twitter/users/folder/$usrfile /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
					/usr/bin/chmod +w /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
					/usr/bin/chown ttusr$ttusr:twittelegram /home/ttusr$ttusr/ttenv/users/twitter/$usrfile
			   fi
			done 
		done
	fi
done
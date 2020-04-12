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

microtime=$(/usr/bin/expr `/usr/bin/date +%s%N` / 1000000000)

if [ "$( /usr/bin/pgrep dowrite.sh | wc -l )" != "2" ]; then
	exit 0
fi
ttusr=$(/usr/bin/whoami)
ttusr=${ttusr:5}

if [ ! -f /home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json ]; then
	exit 1
fi

if [ "$( /usr/bin/cat /home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json | /usr/bin/jq '.subscription.active' )" != "true" ]; then
	exit 1
fi

for crow in $( /usr/bin/cat /home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json | /usr/bin/jq -r '.chats[] | @base64'); do

_cjq() {
	/usr/bin/echo ${crow} | /usr/bin/base64 --decode | /usr/bin/jq -r ${1}
}

isactive=$( _cjq '.active' )
if [ "${isactive}" != "true" ]; then
	continue
fi

ijson=$( _cjq '.wizard' ) 

if [ "$(/usr/bin/echo $ijson | /usr/bin/jq -r '.status')" == "publishing" ]; then

chat=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.chatID')
context=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.context')
user=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.user')

if [ ! -f /home/ttusr$ttusr/ttenv/users/twitter/$user.json ]; then
	continue 
fi

if [ "$( /usr/bin/cat /home/ttusr$ttusr/ttenv/users/twitter/$user.json | /usr/bin/jq '.subscription.active' )" != "true" ]; then
	continue
fi

/usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl set default $user
userid=$( /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -H "https://api.twitter.com" "/1.1/users/show.json?screen_name=$user" | /usr/bin/jq -r '.id_str' )
textYesNo=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.textYesNo')
text=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.text')
if [ "${text}" == "null" ]; then
	text=""
fi
mediaCount=$(/usr/bin/echo $ijson | /usr/bin/jq -r '.mediaCount')
media=$( /usr/bin/echo $ijson | /usr/bin/jq -r '.media')
replyto=$( /usr/bin/echo $ijson | /usr/bin/jq -r '.replyTo')
retweetthis=$( /usr/bin/echo $ijson | /usr/bin/jq -r '.retweetThis')
mediaids=""
if [ "${media}" != "null" ]; then
	for mrow in $( /usr/bin/echo ${media} | /usr/bin/jq -r '.[] | @base64'); do
		_mjq() {
                      	/usr/bin/echo ${mrow} | /usr/bin/base64 --decode | /usr/bin/jq -r ${1}
                }
		url=$(_mjq '.url')
		filename=$(/usr/bin/basename $url)
		/usr/bin/wget -Nq $url -P /home/ttusr$ttusr/ttenv/tmp/media
		mimetype=$(/usr/bin/file -b --mime-type /home/ttusr$ttusr/ttenv/tmp/media/$filename)
		mediacat=$(/usr/bin/echo "TWEET_"${mimetype^^} | /usr/bin/cut -f1 -d"/")
		size=$(/usr/bin/wc -c /home/ttusr$ttusr/ttenv/tmp/media/$filename | /usr/bin/cut -f1 -d" ")
		init=$(  /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -X POST -H upload.twitter.com "/1.1/media/upload.json?additional_owners=$userid" -d "command=INIT&media_type=$mimetype&media_category=$mediacat&total_bytes=$size" )
		mediaid=$(/usr/bin/echo $init | /usr/bin/jq -r '.media_id_string' )				
		/usr/bin/rm -f /home/ttusr$ttusr/ttenv/tmp/chunks/*
		/usr/bin/split -b M /home/ttusr$ttusr/ttenv/tmp/media/$filename /home/ttusr$ttusr/ttenv/tmp/chunks/
		i=0
		for chunk in `ls -v /home/ttusr$ttusr/ttenv/tmp/chunks/`; do 
			if [ -f /home/ttusr$ttusr/ttenv/tmp/chunks/$chunk ]; then
			chunkout=$( /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -X POST -H upload.twitter.com "/1.1/media/upload.json" -d "command=APPEND&media_id=$mediaid&segment_index=$i" --file /home/ttusr$ttusr/ttenv/tmp/chunks/$chunk --file-field "media" )
			i=$(/usr/bin/expr $i + 1)	
			fi
		done
		finalize=$( /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -X POST -H upload.twitter.com "/1.1/media/upload.json" -d "command=FINALIZE&media_id=$mediaid")
		if [ "$(/usr/bin/echo $finalize | /usr/bin/jq -r '.processing_info.state' )" != "null" ] && [ "$(/usr/bin/echo $finalize | /usr/bin/jq -r '.processing_info.state' )" != "succeeded" ]; then
			/usr/bin/sleep 1m
			finalize=$(  /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -t -X GET -H upload.twitter.com "/1.1/media/upload.json?command=STATUS&media_id=$mediaid");
			if [ "$(/usr/bin/echo $finalize | /usr/bin/jq -r '.processing_info.state' )" != "succeeded" ]; then
				/usr/bin/php /home/ttusr$ttusr/ttenv/dowrite.php $chat "Failed publishing your tweet $text. It was not possible to load media file(s) to Twitter."
				exit 1;
			fi		
		fi
		mediaids=$(/usr/bin/echo $mediaids)$(/usr/bin/echo ",")$(/usr/bin/echo $mediaid)
	done
fi
if [ "${mediaids}" != "" ]; then
	mediaids="&media_ids=${mediaids:1}"
fi
if [ "${replyTo}" != "null" ]; then
	replyto="&in_reply_to_status_id=$replyto&auto_populate_reply_metadata=true"
fi 
if [ "${context}" == "retweet" ] ; then
tweet="$( /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -X POST -H https://api.twitter.com "/1.1/statuses/retweet/$retweetthis.json" -d "status=$text$mediaids$replyto" )"
else
tweet="$( /usr/bin/env - `/usr/bin/cat /home/ttusr$ttusr/ttenv/bashenv.sh` /usr/local/rvm/gems/ruby-2.4.2/bin/twurl -X POST -H https://api.twitter.com "/1.1/statuses/update.json" -d "status=$text$mediaids$replyto" )"
fi
tweetid="$( /usr/bin/echo $tweet | /usr/bin/jq -r '.id_str' )"
emicrotime=$(/usr/bin/expr `/usr/bin/date +%s%N` / 1000000000)
cost=$(/usr/bin/expr $emicrotime - $microtime)
if [ "${context}" == "tweet" ] ; then
/usr/bin/php /home/ttusr$ttusr/ttenv/dowrite.php $chat "Twitted! https://twitter.com/$user/status/$tweetid" "/home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json" $user $tweetid $cost
fi
if [ "${context}" == "reply" ] ; then
/usr/bin/php /home/ttusr$ttusr/ttenv/dowrite.php $chat "Replied! https://twitter.com/$user/status/$tweetid" "/home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json" $user $tweetid $cost
fi
if [ "${context}" == "retweet" ] ; then
/usr/bin/php /home/ttusr$ttusr/ttenv/dowrite.php $chat "Retwitted! https://twitter.com/$user/status/$tweetid" "/home/ttusr$ttusr/ttenv/users/telegram/$ttusr.json" $user $tweetid $cost
fi

fi

done

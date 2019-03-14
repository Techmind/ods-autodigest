1) sudo apt-get install php php-curl
2) sudo apt-get install elasticsearch; sudo service elasticsearch start
3) to run web-interface on localhost on port 8000: php -S 0.0.0.0:8000
4) launching: to populate elastic search database run this: php cron/load_data.php $WEB_API_TOKEN

To get $WEB_API_TOKEN, check any xhr request from chrome's development console (ctrl+i default keybinding) and find 'token' parameter in post fields. 






























TODO:

-over 9000) add 'personalization for me', collect all my own likes-> set weights for pluses as +2, set minuses as -2.

-3) divide by # of messages ? so ratings is less scewed by users who message a lot ?

-2) logginh to separate file (with current date,so i cna laucnh this shit in screen and forget about it)

-1) use sql and not elastic

0) 'hot topic detection' daily/weekly, by reactions/comment numbers and positivity/negativity, maybe by channel ?

1) features:
most interesting message by day/week per channel:
- total # reactions,
- reactions / number of members,
- replies / number of members

1.1) PER user:
number of messages <- spammers/contributors =)
number of messages / (DAYS from joining)
number of channels (joined to / messaged to)
positive_reactions
negative_reactions

positive_reactions / number_messages
negative_reactions / number_messages

max_positive_reactions of 1 post - best post
max_negative_reactions of 1 post - worst post

1.2) same for links ?

1.3) per message in thread, find avg number of reactions/comments total/positive/negative (reactions = mii-comments like "this sucks/this is good")
1.3.1) ignore diffrent positive/negative reactions/comments for SAME USERS

UI:
2) bootstrap + better controls for timestamps (add calendar to search)


3) backend and/or js framework
3.5) faster elastic indexing: bulk insert

4) add https://opendatascience.slack.com/archives/C04N3UMSL/p1522148026000368 and fidn most toxic/controversial comments(срачико-детектор =)!!!), people, themes
4.1) sort/highlight messages by positivness/negativness
4.5) use "4" to detect positivness/negativness of emojis, drop emojis from configs

5) find and ignore polls from reactions/negativness detection (jsut ignore _random_talks for now)

6) 

.................

101) test same stuff(sentient/negatvity/reactions) ^ on facebook messages
102) test same stuff on youtube comments/likes ^ on youtube channels like bloggers/polit channels 

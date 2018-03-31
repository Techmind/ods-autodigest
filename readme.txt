1) sudo apt-get install php
2) install elasticsearch
3) php -S 0.0.0.0:8000


TODO:
1) features:
most interesting message by day/week per channel:
- total # reactions,
- reactions / number of members,
- replies / number of members

1.1) PER user:
number of messages
number of messages / (DAYS from joining)
number of channels (joined to / messaged to)
positive_reactions
negative_reactions

positive_reactions / number_messages
negative_reactions / number_messages

max_positive_reactions of 1 post - best post
max_negative_reactions of 1 post - worst post

1.2) same for links ?

UI:
2) bootstrap + better controls for timestamps (add calendar to search)


3) backend and/or js framework
3.5) faster elastic indexing: bulk insert

4) add https://opendatascience.slack.com/archives/C04N3UMSL/p1522148026000368 and fidn most toxic/controversial comments(срачико-детектор =)!!!), people, themes
4.1) sort/highlight messages by positivness/negativness
4.5) use "4" to detect positivness/negativness of emojis, drop emojis from configs

5) find and ignore polls from reactions/negativness detection (jsut ignore _random_talks for now)

.................

101) test same stuff(sentient/negatvity/reactions) ^ on facebook messages

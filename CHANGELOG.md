# Changelog

All notable changes to My Gift are written here, in plain words. Each line links to the
pull request that made the change.

The format is inspired by [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). A version is a
git tag: `1.0.0` for a release, `1.0.0-rc1` for a release candidate (no leading `v`).

## [1.0.0] - 2026-11-25

The first release. My Gift is a website where you list the gifts you wish for, and where your
friends and family reserve a gift or chip in together to pay for it. Seven release
candidates (`1.0.0-rc1` to `1.0.0-rc7`) came before it.

### Gift lists

- Create sections (an occasion, a theme) and fill them with gifts: a title, a description, a
  price and a picture. Edit or delete them whenever you want.
  [#4], [#5], [#6], [#7], [#8]
- Paste the link of a product and My Gift fills in the title, the description, the picture
  and the price when the shop's page allows it. It works on most shops, and it keeps what you
  typed when a page gives nothing. [#18], [#170], [#171], [#176]
- Keep gifts you like in your favourites. [#21]
- Friends see your public gifts even when they are not filed in a section. [#177]
- A dashboard shows your gifts and your figures, and invites you to start your list when it
  is empty. [#28], [#29], [#156]
- A page lists the gifts you reserved or chip in for, sorted by period. [#173]
- Breadcrumbs show where you are. [#32]

### Reserving and chipping in

- Reserve a gift, or give part of its price. You can change your mind and cancel.
  [#22], [#23]
- When the contributions cover the price, the gift is ready to give. Its owner then marks
  it as given, and later archives it. A gift is never declared ready before the contributions
  cover its price. [#4], [#72]
- The price of a gift with several givers is a target they agree on, not a fixed share
  each. [#49]
- Two people can no longer take the same gift at the same moment. [#128], [#130]
- The givers are told when an owner deletes a gift they were on. [#131], [#161]

### Friends

- Your lists are private: only you and your friends see them. You send, accept, refuse,
  cancel and remove friend requests, or block someone. [#55]
- Ask someone to be your friend with your personal invitation link. You can share it or
  choose its words. It replaces the user search of the first versions.
  [#13], [#42], [#159]
- The person you ask gets an email, at most one a day for the same pair. [#144], [#145]
- Remove or block a friend from the "…" menu on their card. [#103]

### Groups

- Friends keep a gift list together in a group: a couple preparing a list for their child, a
  family sharing one. All members have the same rights. The owner is the only one who
  can delete the group, and can hand it over to someone else. The group remembers when each
  member joined. [#61], [#86], [#109]
- A gift of a group stays in the group when its author deletes their account, and a member
  can take it over. [#115], [#117]
- The emails about a group's gift go to every member. [#116]
- A group's sections show in your own list, and its gifts come first on its page.
  [#163], [#167]

### Sharing

- The "Partager" buttons copy a link to a gift, a group or your own list. [#98]
- Messengers unfold a shared link into a card with a title and a picture. The card works
  for six months; the link itself keeps opening the page. [#98], [#169]

### Your account

- Sign up, log in and log out. Confirm your email address before your first login.
  [#3], [#132]
- Reset a forgotten password by email, or stay logged in with "Se souvenir de moi".
  [#50], [#52]
- Choose a pseudo, the name other people see. Your last name is optional.
  [#153], [#154]
- Edit your profile and your picture. Without one, My Gift draws an avatar for you.
  [#9], [#12], [#69]
- The login and the sign-up forms sit side by side. [#155]
- Choose a light theme, a dark theme, or follow your device. [#136]
- Delete your account yourself. An account unused for a year gets a warning email and is
  deleted a month later, unless you come back. An account whose email was never confirmed is
  deleted after 30 days. [#88], [#89]

### Your data and your privacy

- Download your gift lists as CSV, JSON, ODS or XLSX, or all your account data. [#90], [#91]
- Search engines no longer index private pages. [#185]
- Only the owners of a gift or a section can edit or delete it: its author, or every member
  of its group. [#26]
- A gift you may not see stays hidden, even for someone who knows its address. The page of
  someone else's gift or section answers "not found", as if it did not exist.
  [#43], [#44], [#45], [#72]
- Wrong passwords are limited to a few tries. [#87]
- A remembered user is asked for their password again before sensitive pages. [#211]
- A product picture is only downloaded if it is under 20 MB and does not come from a private
  address. [#106]
- Every sensitive button checks a security token. [#81], [#128]
- The site has its own pages: about, FAQ, legal notice, privacy policy and terms of use. The
  FAQ says that My Gift is free. [#121], [#122], [#184]

### Look and feel

- A landing page presents the app, its groups, its friends and its sharing. [#2], [#37],
  [#124], [#209]
- The gift card, the lists, the favourites, the gift page and the forms were redesigned
  around the gift's status and price. [#38], [#40], [#41], [#46], [#47]
- The app takes the violet of its logo. The logo, a gift icon, shows in the header beside the
  app's name and is also the favicon. [#111], [#112], [#143]
- On a phone, the header of a list is lighter so the gifts show. [#166], [#167]
- The list of givers on a gift's page stands out like the other cards. [#123]
- Error pages are in French and look like the app. [#119]
- Pictures are stored as WebP, a lighter format. [#96]
- Forms say what is wrong with what you typed. [#15], [#25]
- Many smaller touches on the header, the footer, the menu, the buttons and the forms.
  [#10], [#11], [#14], [#16], [#17], [#20], [#24], [#27], [#33], [#102], [#105], [#162]

### Emails

- Welcome, email confirmation, a new giver, a giver who left, a friend request: the emails
  look like the app. [#31], [#34], [#57], [#68], [#144]
- They read well in dark and light mode, and keep their colours in Gmail. [#186], [#188],
  [#189]
- Everyone who gave to a gift hears when its price changes, even if an admin changed
  it. [#84]

### Accessibility

My Gift works towards the RGAA, the French accessibility standard, and is not fully
compliant yet. What remains, including a check by hand with a screen reader, is tracked in
[#180](https://github.com/zak39/my-gift/issues/180) for the next version.

- Every page has a language, a skip link, landmarks and headings in order. [#146], [#152],
  [#207]
- Text and badges are contrasted enough. [#151], [#203]
- Keyboard users see where they are, and the focus comes back to the menu after a
  dialog. [#107], [#199], [#201]
- Screen readers hear the status messages and the copied links, and the menu closes with
  the keyboard. [#174], [#183]
- Each form field has its own label, and the sign-up form helps browsers fill in your
  email. [#190], [#208]
- Tooltips can be dismissed, and decorative pictures are hidden from screen readers.
  [#204], [#206]
- The avatar of the connected user and the logo no longer repeat an identifier.
  [#182], [#205]

### Administration

- A back office, reserved to administrators, to see and edit the data. [#70]

### Fixed along the way

- Long titles and names stay inside their layout. [#48]
- The password mismatch message is right. [#51]
- A new giver is announced as a newcomer, not as a changed share. [#82]
- A registration goes on when the avatar service answers nothing. [#97]
- An email's preheader keeps its variables. [#92]
- The terms sentence wraps instead of widening the sign-up form. [#133]
- A friend whose list has no published section no longer ends on an error page. [#210]

### For developers

- Docker Compose deploys prod and preprod. A deploy takes a git tag, and prod only takes
  final tags. [#134], [#138], [#141]
- The test and preprod sites show a "version de test" banner. [#137]
- Logs rotate, so they cannot fill the server's disk. [#202]
- Symfony security updates, the Mailgun bridge and PHP 8.4. [#139], [#140], [#213]
- The project is licensed under AGPL-3.0-or-later. [#214]
- A README and a guide for AI agents (`CLAUDE.md`). [#35], [#212]
- Skills and agents that help an AI assistant with the templates and the avatars.
  [#36], [#71], [#100]
- Cleaner code: controllers filed by URL, atomic and molecule components, one action per
  class, checked CSRF tokens by attribute. [#30], [#56], [#72], [#110], [#118], [#135],
  [#147], [#148], [#149], [#150], [#175], [#181]
- First setup: tools, build and contact links. [#1], [#19], [#142]

[1.0.0]: https://github.com/zak39/my-gift/releases/tag/1.0.0
[#1]: https://github.com/zak39/my-gift/pull/1
[#2]: https://github.com/zak39/my-gift/pull/2
[#3]: https://github.com/zak39/my-gift/pull/3
[#4]: https://github.com/zak39/my-gift/pull/4
[#5]: https://github.com/zak39/my-gift/pull/5
[#6]: https://github.com/zak39/my-gift/pull/6
[#7]: https://github.com/zak39/my-gift/pull/7
[#8]: https://github.com/zak39/my-gift/pull/8
[#9]: https://github.com/zak39/my-gift/pull/9
[#10]: https://github.com/zak39/my-gift/pull/10
[#11]: https://github.com/zak39/my-gift/pull/11
[#12]: https://github.com/zak39/my-gift/pull/12
[#13]: https://github.com/zak39/my-gift/pull/13
[#14]: https://github.com/zak39/my-gift/pull/14
[#15]: https://github.com/zak39/my-gift/pull/15
[#16]: https://github.com/zak39/my-gift/pull/16
[#17]: https://github.com/zak39/my-gift/pull/17
[#18]: https://github.com/zak39/my-gift/pull/18
[#19]: https://github.com/zak39/my-gift/pull/19
[#20]: https://github.com/zak39/my-gift/pull/20
[#21]: https://github.com/zak39/my-gift/pull/21
[#22]: https://github.com/zak39/my-gift/pull/22
[#23]: https://github.com/zak39/my-gift/pull/23
[#24]: https://github.com/zak39/my-gift/pull/24
[#25]: https://github.com/zak39/my-gift/pull/25
[#26]: https://github.com/zak39/my-gift/pull/26
[#27]: https://github.com/zak39/my-gift/pull/27
[#28]: https://github.com/zak39/my-gift/pull/28
[#29]: https://github.com/zak39/my-gift/pull/29
[#30]: https://github.com/zak39/my-gift/pull/30
[#31]: https://github.com/zak39/my-gift/pull/31
[#32]: https://github.com/zak39/my-gift/pull/32
[#33]: https://github.com/zak39/my-gift/pull/33
[#34]: https://github.com/zak39/my-gift/pull/34
[#35]: https://github.com/zak39/my-gift/pull/35
[#36]: https://github.com/zak39/my-gift/pull/36
[#37]: https://github.com/zak39/my-gift/pull/37
[#38]: https://github.com/zak39/my-gift/pull/38
[#40]: https://github.com/zak39/my-gift/pull/40
[#41]: https://github.com/zak39/my-gift/pull/41
[#42]: https://github.com/zak39/my-gift/pull/42
[#43]: https://github.com/zak39/my-gift/pull/43
[#44]: https://github.com/zak39/my-gift/pull/44
[#45]: https://github.com/zak39/my-gift/pull/45
[#46]: https://github.com/zak39/my-gift/pull/46
[#47]: https://github.com/zak39/my-gift/pull/47
[#48]: https://github.com/zak39/my-gift/pull/48
[#49]: https://github.com/zak39/my-gift/pull/49
[#50]: https://github.com/zak39/my-gift/pull/50
[#51]: https://github.com/zak39/my-gift/pull/51
[#52]: https://github.com/zak39/my-gift/pull/52
[#55]: https://github.com/zak39/my-gift/pull/55
[#56]: https://github.com/zak39/my-gift/pull/56
[#57]: https://github.com/zak39/my-gift/pull/57
[#61]: https://github.com/zak39/my-gift/pull/61
[#68]: https://github.com/zak39/my-gift/pull/68
[#69]: https://github.com/zak39/my-gift/pull/69
[#70]: https://github.com/zak39/my-gift/pull/70
[#71]: https://github.com/zak39/my-gift/pull/71
[#72]: https://github.com/zak39/my-gift/pull/72
[#81]: https://github.com/zak39/my-gift/pull/81
[#82]: https://github.com/zak39/my-gift/pull/82
[#84]: https://github.com/zak39/my-gift/pull/84
[#86]: https://github.com/zak39/my-gift/pull/86
[#87]: https://github.com/zak39/my-gift/pull/87
[#88]: https://github.com/zak39/my-gift/pull/88
[#89]: https://github.com/zak39/my-gift/pull/89
[#90]: https://github.com/zak39/my-gift/pull/90
[#91]: https://github.com/zak39/my-gift/pull/91
[#92]: https://github.com/zak39/my-gift/pull/92
[#96]: https://github.com/zak39/my-gift/pull/96
[#97]: https://github.com/zak39/my-gift/pull/97
[#98]: https://github.com/zak39/my-gift/pull/98
[#100]: https://github.com/zak39/my-gift/pull/100
[#102]: https://github.com/zak39/my-gift/pull/102
[#103]: https://github.com/zak39/my-gift/pull/103
[#105]: https://github.com/zak39/my-gift/pull/105
[#106]: https://github.com/zak39/my-gift/pull/106
[#107]: https://github.com/zak39/my-gift/pull/107
[#109]: https://github.com/zak39/my-gift/pull/109
[#110]: https://github.com/zak39/my-gift/pull/110
[#111]: https://github.com/zak39/my-gift/pull/111
[#112]: https://github.com/zak39/my-gift/pull/112
[#115]: https://github.com/zak39/my-gift/pull/115
[#116]: https://github.com/zak39/my-gift/pull/116
[#117]: https://github.com/zak39/my-gift/pull/117
[#118]: https://github.com/zak39/my-gift/pull/118
[#119]: https://github.com/zak39/my-gift/pull/119
[#121]: https://github.com/zak39/my-gift/pull/121
[#122]: https://github.com/zak39/my-gift/pull/122
[#123]: https://github.com/zak39/my-gift/pull/123
[#124]: https://github.com/zak39/my-gift/pull/124
[#128]: https://github.com/zak39/my-gift/pull/128
[#130]: https://github.com/zak39/my-gift/pull/130
[#131]: https://github.com/zak39/my-gift/pull/131
[#132]: https://github.com/zak39/my-gift/pull/132
[#133]: https://github.com/zak39/my-gift/pull/133
[#134]: https://github.com/zak39/my-gift/pull/134
[#135]: https://github.com/zak39/my-gift/pull/135
[#136]: https://github.com/zak39/my-gift/pull/136
[#137]: https://github.com/zak39/my-gift/pull/137
[#138]: https://github.com/zak39/my-gift/pull/138
[#139]: https://github.com/zak39/my-gift/pull/139
[#140]: https://github.com/zak39/my-gift/pull/140
[#141]: https://github.com/zak39/my-gift/pull/141
[#142]: https://github.com/zak39/my-gift/pull/142
[#143]: https://github.com/zak39/my-gift/pull/143
[#144]: https://github.com/zak39/my-gift/pull/144
[#145]: https://github.com/zak39/my-gift/pull/145
[#146]: https://github.com/zak39/my-gift/pull/146
[#147]: https://github.com/zak39/my-gift/pull/147
[#148]: https://github.com/zak39/my-gift/pull/148
[#149]: https://github.com/zak39/my-gift/pull/149
[#150]: https://github.com/zak39/my-gift/pull/150
[#151]: https://github.com/zak39/my-gift/pull/151
[#152]: https://github.com/zak39/my-gift/pull/152
[#153]: https://github.com/zak39/my-gift/pull/153
[#154]: https://github.com/zak39/my-gift/pull/154
[#155]: https://github.com/zak39/my-gift/pull/155
[#156]: https://github.com/zak39/my-gift/pull/156
[#159]: https://github.com/zak39/my-gift/pull/159
[#161]: https://github.com/zak39/my-gift/pull/161
[#162]: https://github.com/zak39/my-gift/pull/162
[#163]: https://github.com/zak39/my-gift/pull/163
[#166]: https://github.com/zak39/my-gift/pull/166
[#167]: https://github.com/zak39/my-gift/pull/167
[#169]: https://github.com/zak39/my-gift/pull/169
[#170]: https://github.com/zak39/my-gift/pull/170
[#171]: https://github.com/zak39/my-gift/pull/171
[#173]: https://github.com/zak39/my-gift/pull/173
[#174]: https://github.com/zak39/my-gift/pull/174
[#175]: https://github.com/zak39/my-gift/pull/175
[#176]: https://github.com/zak39/my-gift/pull/176
[#177]: https://github.com/zak39/my-gift/pull/177
[#181]: https://github.com/zak39/my-gift/pull/181
[#182]: https://github.com/zak39/my-gift/pull/182
[#183]: https://github.com/zak39/my-gift/pull/183
[#184]: https://github.com/zak39/my-gift/pull/184
[#185]: https://github.com/zak39/my-gift/pull/185
[#186]: https://github.com/zak39/my-gift/pull/186
[#188]: https://github.com/zak39/my-gift/pull/188
[#189]: https://github.com/zak39/my-gift/pull/189
[#190]: https://github.com/zak39/my-gift/pull/190
[#199]: https://github.com/zak39/my-gift/pull/199
[#201]: https://github.com/zak39/my-gift/pull/201
[#202]: https://github.com/zak39/my-gift/pull/202
[#203]: https://github.com/zak39/my-gift/pull/203
[#204]: https://github.com/zak39/my-gift/pull/204
[#205]: https://github.com/zak39/my-gift/pull/205
[#206]: https://github.com/zak39/my-gift/pull/206
[#207]: https://github.com/zak39/my-gift/pull/207
[#208]: https://github.com/zak39/my-gift/pull/208
[#209]: https://github.com/zak39/my-gift/pull/209
[#210]: https://github.com/zak39/my-gift/pull/210
[#211]: https://github.com/zak39/my-gift/pull/211
[#212]: https://github.com/zak39/my-gift/pull/212
[#213]: https://github.com/zak39/my-gift/pull/213
[#214]: https://github.com/zak39/my-gift/pull/214

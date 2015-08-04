#! /usr/bin/env python

import filecmp
import system
from os import remove

system("wget -O- https://www.cs.ubc.ca/~mglgms/mta/redirect_target.html >out.txt 2> /dev/null")
if filecmp.cmp('out.txt', 'fetch_target.html'):
	print 'htaccess works. Hurray!'
	remove('out.txt')
else:
	print 'Doesn\'t work :('

import json
from pony.orm import *
import settings
import random

@db_session
def product_insert():
	f1 = open('./basic_info.txt', 'r')
	f2 = open('./more_info.txt', 'r')
	f3 = open('./detail.txt', 'r')
	lines = f1.readlines()
	pics = f2.readlines()
	descs = f3.readlines()

	categories = {}
	categories['turboprops'] = 4
	categories['single-piston'] = 5
	categories['private-jets'] = 6
	categories['twin-piston'] = 7
	categories['helicopter'] = 8
	categories['light'] = 9
	categories['military-classic-vintage'] = 10

	ven = {}
	index = 2

	for line in lines:
		vendor = line.split(';;')
		vendor = vendor[0].split('/')
		if vendor[3] not in ven:
			ven[vendor[3]] = index
			index += 1
			# vendor_id = db.insert("vendor",
			# 	name=vendor[3],
			# 	returning='id')

	# i = 0
	# s = ''
	# for line in lines[:10]:
	# 	if i == 0 and line != '\n':
	# 		s += line
	# 		s += ' '
	# 		i += 1
	# 	elif i == 1 and line != '\n':
	# 		s += line
	# 		print(s)
	# 		s = ''
	# 		i -= 1

	s = ''
	for desc in descs:
		s += desc

	s = s.split('-----------------\n')

	for i in range(0, len(lines)):
		infos = lines[i].split(';;')
		pic = pics[i]
		info = infos[0].split('/')
		cate = info[2]
		vendor = info[3]
		vp = 1000000 + random.randrange(-300000,300000,50000)
		rp = vp + random.randrange(50000,300000,50000)
		product_id = db.insert("product", 
			name=infos[1],
			description=str(s[i]), 
			year=infos[2],
			vendorprice=vp,
			retailprice=rp,
			stock=100,
			sold=0,
			picture=pic,
			cateid=categories[cate],
			vendorid=ven[vendor],
			returning='id')


if __name__ == '__main__':
	db = Database()
	db.bind(**settings.db_params)
	product_insert()
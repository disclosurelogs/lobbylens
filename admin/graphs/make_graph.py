#!/usr/bin/python
import MySQLdb
import math
from genshi.template import TemplateLoader

DB_HOST = "localhost"
DB_USER = "team7"
DB_PASS = ""
DB_NAME = "team7"


__db = MySQLdb.connect(host=DB_HOST ,user=DB_USER ,passwd=DB_PASS,db=DB_NAME, charset = "utf8", use_unicode = True)
__db.autocommit(True)
cur = __db.cursor()

# this is expensive
query = "select t1.supplierPostcode, t2.totalAmount from contractnotice as t1 inner join supplier_money as t2 on t1.supplierABN = t2.supplierABN"
cur.execute(query)
row_list = cur.fetchall()
#cur.close()











from GChartWrapper import *
# Using text markers in a bar chart
G = HorizontalBarGroup([[40,60],[50,30]], encoding='text')
G.size(200,125)
G.marker('tApril mobile hits','black',0,0,13)
G.marker('tMay mobile hits','black',0,1,13,-1)
G.marker('tApril desktop hits','black',1,0,13)
G.marker('tMay desktop hits', 'black',1,1,13)
G.color('FF9900','FFCC33')




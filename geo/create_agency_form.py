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

query = "select DISTINCT agencyName from contractnotice ORDER BY agencyName"
cur.execute(query)
loc_list = cur.fetchall()
cur.close()

for i in loc_list:
    print "<option>%s</option>" % i


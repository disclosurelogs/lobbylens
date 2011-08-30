#!/usr/bin/python
import MySQLdb
import math
from genshi.template import TemplateLoader

DB_HOST = "localhost"
DB_USER = "team7"

DB_USER = "root"
DB_PASS = ""

__db = MySQLdb.connect(host=DB_HOST ,user=DB_USER ,passwd=DB_PASS,db=DB_NAME, charset = "utf8", use_unicode = True)
__db.autocommit(True)
cur = __db.cursor()

query = "select supplierABN,value from contractnotice"
cur.execute(query)
contract_list = cur.fetchall()
#cur.close()


for row in contract_list:
    if row[0] > 0:
        print "%s: %i" % (row[0], row[1])
        query = "select * from supplier_money where supplierABN = '%s'" % row[0]
        cur.execute(query)
        if cur.rowcount == 1:
            query = "update supplier_money set totalAmount = totalAmount + %i where supplierABN = '%s'" % (row[1], row[0])
        elif cur.rowcount == 0:
            query = "insert into supplier_money values ('%s',%i)" % (row[0], row[1])
        else:
            raise Exception
        cur.execute(query)

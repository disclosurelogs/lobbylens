#!/usr/bin/python

import MySQLdb
import math
import time
import os
import md5
import cgi, cgitb 
from genshi.template import TemplateLoader
from random import random

DB_HOST = "localhost"
DB_USER = "team7"
DB_PASS = ""
DB_NAME = "team7"

__db = MySQLdb.connect(host=DB_HOST ,user=DB_USER ,passwd=DB_PASS,db=DB_NAME, charset = "utf8", use_unicode = True)
__db.autocommit(True)
cur = __db.cursor()

#rst = "Department of Health and Ageing"

form = cgi.FieldStorage()
rst = form.getvalue('agn')

timeout = 604800

if not rst:
    rst = "Centrelink"
    
fname = "cache/%s.xml" % rst

# Tell the browser how to render the text
print "Content-Type: text/plain\n\n"

def yield_info():
    query = "select DISTINCT t1.supplierName, t1.description, t1.supplierABN, t2.lat, t2.lon from contractnotice as t1 right join postcodes as t2 on t1.supplierPostcode = t2.postcode where t1.agencyName = '%s'" % rst
    cur.execute(query)
    loc_list = cur.fetchall()
    cur.close()
    for i in loc_list:
        if i[3] and i[4] and (i[0] != "undefined"):
            vizurl = "http://team7.govhack.net.tmp.anchor.net.au/networkgraph.php?node_id=supplier-%s" % i[2]
            desc = "<![CDATA[ <a href='%s'>View Relationships</a> ]]>" % vizurl

            lat = float(i[3])+(random()*.008)
            long = float(i[4])+(random()*.01)
            yield {
                'rdfaboutl': {'rdf:about': vizurl},
                'resource': {'resource': vizurl},
                'vizurl': vizurl,
                'name': i[0],
                'desc': i[1],
                'abn': i[2],
                'lat': lat,
                'long': long,
            }

if __name__ == "__main__":
    if os.path.exists(fname) and (os.stat(fname).st_mtime > time.time() - timeout):
        fh = open(fname, "r")
        content = fh.read()
        fh.close()
        print content
    else:
        loader = TemplateLoader(['.'])
        template = loader.load('template.xml')
        stream = template.generate(collection=yield_info())
        fh = file(fname, mode="w")
        s_result = stream.render()
        fh.write(s_result)
        fh.close()
        print s_result

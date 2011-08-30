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


def yield_info():

coord_list = []
for i in row_list:
    if len(int(i[0])) > 50:
        query = "select lon, lat from postcodes where postcode = %s" % i[0]
        cur.execute(query)
        print cur.fetchone()
        print temp

        try:
            lon =  float(temp[0]) * 20037508.34 / 180
            lat = math.log(math.tan((90 + float(temp[1])) * math.pi / 360)) / (math.pi / 180)
            lat = lat * 20037508.34 / 180
            coords = '%s, %s' % (lon, lat)
            coord_list.append(coords)
        except:
            print "some error"

        color = '000000'
        yield {
            'name': name,
            'coordinates': coords,
            'color': color,
            }

if __name__ == "__main__":
    loader = TemplateLoader(['.'])
    template = loader.load('template-lobby.kml')
    stream = template.generate(collection=yield_info())
    filename = 'lobby.kml'
    f = open(filename, 'w')
    f.write(stream.render())
    f.close()

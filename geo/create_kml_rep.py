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

query = "select t1.lat, t1.long, t2.firstname, t2.surname from division_geo as t1 inner join representatives as t2 on t1.division_id = t2.division_id"
cur.execute(query)
loc_list = cur.fetchall()
cur.close()

def yield_info():
    for i in loc_list:
        #lon =  float(i[1]) * 20037508.34 / 180
        #lat = math.log(math.tan((90 + float(i[0])) * math.pi / 360)) / (math.pi / 180)
        #lat = lat * 20037508.34 / 180;
        #coords = '%s, %s' % (lon, lat)
        coords = '%s, %s' % (i[1], i[0])
        color = '000000'
        name = '%s %s' % (i[2], i[3])
        yield {
            'name': name,
            'coordinates': coords,
            'color': color,
            }

if __name__ == "__main__":
    loader = TemplateLoader(['.'])
    template = loader.load('template-polit.kml')
    stream = template.generate(collection=yield_info())
    filename = 'politicians.kml'
    f = open(filename, 'w')
    f.write(stream.render())
    f.close()


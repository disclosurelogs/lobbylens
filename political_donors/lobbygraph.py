import networkx as nx
#import matplotlib.pyplot as plt
from xml.dom.minidom import parse
G=nx.Graph()
dom = parse("political_donors.xml")
for node in dom.getElementsByTagName('node'):
	nodeID = G.add_node(node.attributes["id"].value)
        if "label" in node.attributes.keys():
		G.node[node.attributes["id"].value]['label'] = node.attributes["label"].value
	if "weight" in node.attributes.keys():
		G.node[node.attributes["id"].value]['weight'] = node.attributes["weight"].value
	if "graphic_fill_color" in node.attributes.keys():
		G.node[node.attributes["id"].value]['color'] = node.attributes["graphic_fill_color"].value

for edge in dom.getElementsByTagName('edge'):
	if "weight" in edge.attributes.keys():
		G.add_edge(edge.attributes["tail_node_id"].value, edge.attributes["head_node_id"].value, weight=edge.attributes["weight"].value)
	else:
		G.add_edge(edge.attributes["tail_node_id"].value, edge.attributes["head_node_id"].value)

#nx.write_pajek(G,"test.pj")
nx.write_gexf(G,"political_donors.gexf")
#nx.draw(G)
#plt.show()


import java.io.ObjectInputStream.GetField;
import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.SQLWarning;
import java.sql.Statement;
import java.util.HashMap;
import java.util.Map;

import com.tinkerpop.blueprints.pgm.Edge;
import com.tinkerpop.blueprints.pgm.TransactionalGraph.Conclusion;
import com.tinkerpop.blueprints.pgm.Vertex;
import com.tinkerpop.blueprints.pgm.impls.orientdb.OrientGraph;

public class Importer {

	public static void main(String[] argv) {

		System.out.println("-------- PostgreSQL "
				+ "JDBC Connection Testing ------------");

		try {

			Class.forName("org.postgresql.Driver");

		} catch (ClassNotFoundException e) {

			System.out.println("Where is your PostgreSQL JDBC Driver? "
					+ "Include in your library path!");
			e.printStackTrace();

		}

		System.out.println("PostgreSQL JDBC Driver Registered!");

		Connection conn = null;

		try {

			conn = DriverManager.getConnection(
					"jdbc:postgresql://127.0.0.1:5432/contractDashboard",
					"postgres", "snmc");

		} catch (SQLException e) {

			System.out.println("Connection Failed! Check output console");
			e.printStackTrace();

		}

		if (conn != null) {
			System.out.println("You made it, take control your database now!");
		} else {
			System.out.println("Failed to make connection!");
		}
		try {
			// Print all warnings
			for (SQLWarning warn = conn.getWarnings(); warn != null; warn = warn
					.getNextWarning()) {
				System.out.println("SQL Warning:");
				System.out.println("State  : " + warn.getSQLState());
				System.out.println("Message: " + warn.getMessage());
				System.out.println("Error  : " + warn.getErrorCode());
			}

			// Get a statement from the connection
			Statement stmt = conn.createStatement();

			// Execute the query
			ResultSet rs = stmt
					.executeQuery("SELECT contractnotice.\"agencyName\", "
							+ "  contractnotice.\"supplierABN\",contractnotice.\"supplierName\",sum(value) as sum "
							+ "FROM  public.contractnotice where contractnotice.\"agencyName\" != 'Department of Defence'"
							+ " AND contractnotice.\"agencyName\" != 'Defence Materiel Organisation' GROUP BY contractnotice.\"agencyName\", "
							+ "  contractnotice.\"supplierABN\",contractnotice.\"supplierName\"");
			OrientGraph graph = null;
			try {
				graph = new OrientGraph("local:C:/tmp/graphdb");

				String previousAgency = "";

				HashMap<String, Object> supplierIDs = new HashMap<String, Object>();
				HashMap<String, Object> agencyIDs = new HashMap<String, Object>();

				// Loop through the result set
				while (rs.next()) {
					try {

						Object supplierID, agencyID;
						String supplierKey;
						Vertex agency;
						if (agencyIDs.get(rs.getString("agencyName")) == null) {
							agency = graph.addVertex(null);
							agency.setProperty("Label",
									rs.getString("agencyName").toString());
							agency.setProperty("type", "agency");
							agencyIDs.put(rs.getString("agencyName"),
									agency.getId());
							if (agencyIDs.size() % 100 == 0) {
								System.out.println("Agency " + agency.getId());
							}
						} else {
							agency = graph.getVertex(agencyIDs.get(rs
									.getString("agencyName")));
						}
						agencyID = agencyIDs.get(rs.getString("agencyName"));

						Vertex supplier;
						if (rs.getString("supplierABN") != "0"
								&& rs.getString("supplierABN") != "") {
							supplierKey = rs.getString("supplierABN");
						} else {
							supplierKey = rs.getString("supplierName");
						}
						// inject some data
						if (supplierIDs.get(supplierKey) == null) {
							supplier = graph.addVertex(null);
							supplier.setProperty("Label",
									rs.getString("supplierName").toString());
							supplier.setProperty("type", "supplier");
							supplierIDs.put(supplierKey, supplier.getId());
							if (supplierIDs.size() % 1000 == 0) {
								System.out.println("Supplier "
										+ supplier.getId());
							}
						} else {
							supplier = graph.getVertex(supplierIDs
									.get(supplierKey));
						}
						supplierID = supplierIDs.get(supplierKey);

						Edge agencyKnowsSupplier = graph.addEdge(null, agency,
								supplier, "knows");
						agencyKnowsSupplier.setProperty("Weight",
								rs.getDouble("sum"));
						graph.stopTransaction(Conclusion.SUCCESS);
					} catch (Exception e) {

						graph.stopTransaction(Conclusion.FAILURE);
					}
				}

			} finally {
				if (graph != null)
					graph.shutdown();
			}
			// Close the result set, statement and the connection
			rs.close();
			stmt.close();
			conn.close();
		} catch (SQLException se) {
			System.out.println("SQL Exception:");

			// Loop through the SQL Exceptions
			while (se != null) {
				System.out.println("State  : " + se.getSQLState());
				System.out.println("Message: " + se.getMessage());
				System.out.println("Error  : " + se.getErrorCode());

				se = se.getNextException();
			}
		}
	}
}
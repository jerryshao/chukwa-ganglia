package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor;

import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.List;

import org.apache.hadoop.chukwa.contrib.chukwa_ganglia.ChukwaGangliaConstants;
import org.apache.hadoop.chukwa.contrib.chukwa_ganglia.MetricStoreDest;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;
import org.apache.hadoop.hbase.util.Bytes;
import org.apache.log4j.Logger;
import org.json.simple.JSONObject;
import org.json.simple.JSONValue;



public class GangliaSystemMetricsMapper 
	extends GangliaAbstractMapperProcessor implements ChukwaGangliaConstants {

	private static final String gridName = "unspecified";
	private static Logger log = Logger.getLogger(GangliaSystemMetricsMapper.class);
	
	@Override
	protected void parse(String ParseStr, Map<String, List<Put>> putRecord, 
			Map<String, List<Increment>> incrRecord) throws Throwable {
		JSONObject recordObj = (JSONObject) JSONValue.parse(ParseStr);
		if (recordObj == null) {
			return;
		}
		
		long ts = 0;
		List<Put> metricAttrList = new ArrayList<Put>();
		List<Put> hostClusterList = new ArrayList<Put>();
	
		Map<MetricStoreDest, List<Put>> metricPutMap =
				new HashMap<MetricStoreDest, List<Put>>();
		Map<MetricStoreDest, List<Increment>> metricIncrMap = 
				new HashMap<MetricStoreDest, List<Increment>>();
		for (MetricStoreDest ds : MetricStoreDest.values()) {
			metricPutMap.put(ds, new ArrayList<Put>());
			metricIncrMap.put(ds, new ArrayList<Increment>());
		}
		
		for (Object k : recordObj.keySet()) {
			Object obj = recordObj.get(k);
			if (obj == null) continue;
			
			JSONObject val = (JSONObject) obj;
			for (Object o : val.keySet()) {
				Object v = val.get(o);
				if (v == null || ((JSONObject)v).get("VAL") == null)
					continue;
				
				ts = Long.parseLong(
						((JSONObject)v).get("LASTUPDATE").toString());
				long period = Long.parseLong(((JSONObject)v).get("PERIOD").toString());
				String cf = (String) ((JSONObject)v).get("CF");
				String af = (String) ((JSONObject)v).get("AF");
				String type = (String) ((JSONObject)v).get("TYPE");
				
				//combine metric data Put & Increment
				for (MetricStoreDest ds : MetricStoreDest.values()) {
					long lower = ts / (period * ds.consolidateNum);
					long calTs = lower * (period * ds.consolidateNum);
					
					long rowTs = calTs - calTs % ds.second;
					long colTs = calTs % ds.second;
					
					if (ds.name().startsWith("HOST")) {
						//combine row key
						StringBuilder row = new StringBuilder()
							.append(o).append(":")
							.append(currChunk.getSource()).append(":")
							.append(rowTs);
						
						//generate value according to cf and type
						if (cf.equalsIgnoreCase("LAST")) {
							Put pt = new Put(row.toString().getBytes());
							
							if (type.equalsIgnoreCase("string")) {
								String value = (String) ((JSONObject)v).get("VAL");
								pt.add("V".getBytes(), String.valueOf(colTs).getBytes(),
										Bytes.toBytes(value));
							} else {
								long value = (Long) ((JSONObject)v).get("VAL");
								pt.add("V".getBytes(), String.valueOf(colTs).getBytes(),
										Bytes.toBytes(value));
							}
							metricPutMap.get(ds).add(pt);
							
						} else if (cf.equalsIgnoreCase("AVG") 
								|| cf.equalsIgnoreCase("SUM")) {
							Increment inc = new Increment(row.toString().getBytes());
							long value = (Long)((JSONObject)v).get("VAL");
							inc.addColumn("V".getBytes(), String.valueOf(colTs).getBytes(), value);
							
							metricIncrMap.get(ds).add(inc);
						} else {
							continue;
						}
											
					} else if (ds.name().startsWith("CLUSTER")) {
						String[] list = 
								new String[] {currChunk.getTag("cluster"), gridName};
						
						for (String s : list) {
							//combine row key
							StringBuilder row = new StringBuilder()
								.append(o).append(":")
								.append(s).append(":")
								.append(rowTs);
							
							//generate value according to af and type
							if (af.equalsIgnoreCase("LAST")) {
								Put pt= new Put(row.toString().getBytes());
								
								if (type.equalsIgnoreCase("string")) {
									String value = (String) ((JSONObject)v).get("VAL");
									pt.add("V".getBytes(), String.valueOf(colTs).getBytes(),
											Bytes.toBytes(value));
								} else {
									long value = (Long) ((JSONObject)v).get("VAL");
									pt.add("V".getBytes(), String.valueOf(colTs).getBytes(),
											Bytes.toBytes(value));
								}
								metricPutMap.get(ds).add(pt);
								
							} else if (af.equalsIgnoreCase("AVG") 
									|| af.equalsIgnoreCase("SUM")) {
								Increment inc = new Increment(row.toString().getBytes());
								long value = (Long)((JSONObject)v).get("VAL");
								inc.addColumn("V".getBytes(), String.valueOf(colTs).getBytes(), value);
								
								metricIncrMap.get(ds).add(inc);
							} else {
								continue;
							}
						}
					} else {
						throw new IOException("unknown name " + ds.name());
					}
				}
								
				//combine metric attribute Put
				StringBuilder ms = new StringBuilder()
					.append(currChunk.getSource()).append(":").append((String)o);

				Put mt = new Put(ms.toString().getBytes());
				for (Object e : ((JSONObject)v).keySet()) {
					mt.add("property".getBytes(), ((String)e).getBytes(), 
							((JSONObject)v).get(e).toString().getBytes());
				}
				metricAttrList.add(mt);
			}
		}
		
		for (MetricStoreDest ds : MetricStoreDest.values()) {
			putRecord.put(ds.sourceString, metricPutMap.get(ds));
			incrRecord.put(ds.sourceString, metricIncrMap.get(ds));
		}

		putRecord.put(METRIC_ATTR_TABLE,metricAttrList);
		
		//combine host cluster Put
		String row = 
				currChunk.getTag("cluster") + ":" + currChunk.getSource();
		Put HostClusterPut = new Put(row.getBytes());
		HostClusterPut.add("property".getBytes(), 
				"LASTUPDATE".getBytes(), String.valueOf(ts).getBytes());
		hostClusterList.add(HostClusterPut);
		putRecord.put(HOST_CLUSTER_TABLE, hostClusterList);	
	}
}
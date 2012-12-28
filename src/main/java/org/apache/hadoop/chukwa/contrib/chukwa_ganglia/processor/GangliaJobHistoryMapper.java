package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.hadoop.chukwa.contrib.chukwa_ganglia.ChukwaGangliaConstants;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;
import org.apache.log4j.Logger;
import org.json.simple.JSONObject;

public class GangliaJobHistoryMapper 
	extends GangliaAbstractMapperProcessor implements ChukwaGangliaConstants {

	private static Logger log = Logger.getLogger(GangliaJobHistoryMapper.class);
	
	@SuppressWarnings("unchecked")
	@Override
	protected void parse(String ParseStr, Map<String, List<Put>> putRecord,
			Map<String, List<Increment>> incrRecord) throws Throwable {
		
		String[] jobHistStrs = ParseStr.split("\n");
		
		Map<String, String> jobSummary = new HashMap<String, String>();
		Map<String, Map<String, String>> tasks = 
				new HashMap<String, Map<String, String>>();
		Map<String, Map<String, String>> mapAttempts = 
				new HashMap<String, Map<String, String>>();
		Map<String, Map<String, String>> reduceAttemps = 
				new HashMap<String, Map<String, String>>();
		
		Map<String, String> splits = new HashMap<String, String>();
		String jobid = null;
		
		for (String str : jobHistStrs) {
			splits.clear();
			
			int index = str.indexOf(" ");
			if (index == -1) {
				log.warn("No proper record type can be found");
				continue;
			}
			
			String recordType = str.substring(0, index);
			
	        String remaining = str.substring(index).trim();
	        String[] parts = remaining.split("\" ");
	        for(String part : parts){
	            String key = "";
	            String value = "";
	            if(part.indexOf("=\"") != -1){
	                String[] kVal = part.split("=\"");
	                if(kVal[0] != null) key = kVal[0];
	                if(kVal.length > 1 && kVal[1] != null) value = kVal[1];
	                if(!key.equals("")) {
	                    if (!key.equals("COUNTERS")) {
	                    	splits.put(key, value);
	                    }
	                }
	            }
	        }
	        
	        if (recordType.equals("Job")) {
	        	parseJobHistory(splits, jobSummary);
	        } else if (recordType.equals("Task")) {
	        	parseMRTask(splits, tasks);
	        } else if (recordType.equals("MapAttempt")) {
	        	parseMRAttempts(splits, mapAttempts);
	        } else if (recordType.equals("ReduceAttempt")) {
	        	parseMRAttempts(splits, reduceAttemps);
	        } else {
	        	continue;
	        }
		
	        if (splits.containsKey("JOBID")) {
				jobid = splits.get("JOBID");
			}
		}
		
		if (jobSummary.size() == 0) {
			return;
		}
		
		List<Put> jobPuts = new ArrayList<Put>();
		
		StringBuilder row = new StringBuilder()
			.append(currChunk.getTag("cluster")).append(":")
			.append(jobid);
		
		Put jobPut = new Put(row.toString().getBytes());
		for (Map.Entry<String, String> e : jobSummary.entrySet()) {
			jobPut.add("summary".getBytes(), 
					e.getKey().getBytes(), e.getValue().getBytes());
		}
		jobPuts.add(jobPut);
		
		if (tasks.size() != 0) {
			Put taskPut = new Put(row.toString().getBytes());
			for (Map.Entry<String, Map<String, String>> e : tasks.entrySet()) {
				JSONObject obj = new JSONObject();
				obj.putAll(e.getValue());
				taskPut.add("task".getBytes(), 
						e.getKey().getBytes(), obj.toJSONString().getBytes());
				
			}
			jobPuts.add(taskPut);
		}
		
		if (mapAttempts.size() != 0) {
			Put put = new Put(row.toString().getBytes());
			for (Map.Entry<String, Map<String, String>> e : mapAttempts.entrySet()) {
				JSONObject obj = new JSONObject();
				obj.putAll(e.getValue());
				put.add("mapAttempt".getBytes(), 
						e.getKey().getBytes(), obj.toJSONString().getBytes());
				
			}
			jobPuts.add(put);		
		}
		
		if (reduceAttemps.size() != 0) {
			Put put = new Put(row.toString().getBytes());
			for (Map.Entry<String, Map<String, String>> e : reduceAttemps.entrySet()) {
				JSONObject obj = new JSONObject();
				obj.putAll(e.getValue());
				put.add("reduceAttempt".getBytes(), 
						e.getKey().getBytes(), obj.toJSONString().getBytes());
				
			}
			jobPuts.add(put);		
		}
		
		putRecord.put(JOB_HISTORY_TABLE, jobPuts);
	}
	
	protected void parseJobHistory(final Map<String, String> splits, 
			final Map<String, String> jobSummary) {	
		for (Map.Entry<String, String> e : splits.entrySet()) {
			jobSummary.put(e.getKey(), e.getValue());
		}
	}
	
	protected void parseMRTask(final Map<String, String> splits, 
			final Map<String, Map<String, String>> tasks) {
		if (!splits.containsKey("TASK_TYPE") || !splits.containsKey("TASKID")) {
			return;
		}
		
		String taskID = splits.get("TASKID");
		Map<String, String> tmp = tasks.get(taskID);
		if (tmp == null) {
			tmp = new HashMap<String, String>();
			tasks.put(taskID, tmp);
		}
		
		for (Map.Entry<String, String> e : splits.entrySet()) {
			tmp.put(e.getKey(), e.getValue());
		}
	}
	
	protected void parseMRAttempts(final Map<String, String> splits, 
			final Map<String, Map<String, String>> attempts) {
		String taskID = splits.get("TASK_ATTEMPT_ID");
		Map<String, String> tmp = attempts.get(taskID);
		if (tmp == null) {
			tmp = new HashMap<String, String>();
			attempts.put(taskID, tmp);
		}
		
		for (Map.Entry<String, String> e : splits.entrySet()) {
			tmp.put(e.getKey(), e.getValue());
		}
	}
}

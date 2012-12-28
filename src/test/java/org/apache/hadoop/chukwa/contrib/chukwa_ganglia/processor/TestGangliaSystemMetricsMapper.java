package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor;

import static org.junit.Assert.*;

import java.io.File;
import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;


import org.apache.hadoop.chukwa.Chunk;
import org.apache.hadoop.chukwa.datacollection.agent.ChukwaAgent;
import org.apache.hadoop.chukwa.datacollection.agent.ChukwaAgent.AlreadyRunningException;
import org.apache.hadoop.chukwa.datacollection.connector.ChunkCatcherConnector;
import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;
import org.junit.Before;
import org.junit.Test;

public class TestGangliaSystemMetricsMapper {

	private Chunk chunk = null;
	
	@Before
	public void init() 
			throws IOException, AlreadyRunningException, InterruptedException {	
		Configuration conf = new Configuration();
		File baseDir = new File(System.getProperty("test.build.dir", "/tmp"));
		conf.set("chukwaAgent.checkpoint.dir", baseDir.getCanonicalPath());
		conf.setBoolean("chukwaAgent.checkpoint.enabled", false);
		conf.set("chukwaAgent.control.port", "0");
		conf.set("chukwaAgent.http.port", "9091");
		ChukwaAgent agent = new ChukwaAgent(conf);
		ChunkCatcherConnector chunks = new ChunkCatcherConnector();
		chunks.start();
		
		agent.processAddCommand("add org.apache.hadoop.chukwa.contrib." +
				"chukwa_ganglia.GangliaSystemMetricsAdaptor" +
				" GangliaSystemMetrics 10 0");
		
		chunk = chunks.waitForAChunk();
	}
	
	@Test
	public void testProcess() {
		GangliaMapperProcessor processor = new GangliaSystemMetricsMapper();
		Map<String, List<Put>> data = new HashMap<String, List<Put>>();
		Map<String, List<Increment>> incrData = 
				new HashMap<String, List<Increment>>();
		processor.process(chunk, data, incrData);
		
		for (String s : data.keySet()) {
			assertNotNull(data.get(s));
		}
		for (String s : incrData.keySet()) {
			assertNotNull(incrData.get(s));
		}
		
	}

}

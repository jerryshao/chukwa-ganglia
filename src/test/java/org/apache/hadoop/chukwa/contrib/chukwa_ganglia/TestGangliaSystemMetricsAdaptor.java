package org.apache.hadoop.chukwa.contrib.chukwa_ganglia;

import static org.junit.Assert.*;

import java.io.File;
import java.io.IOException;
import java.net.InetAddress;


import org.apache.hadoop.chukwa.Chunk;
import org.apache.hadoop.chukwa.datacollection.agent.ChukwaAgent;
import org.apache.hadoop.chukwa.datacollection.agent.ChukwaAgent.AlreadyRunningException;
import org.apache.hadoop.chukwa.datacollection.connector.ChunkCatcherConnector;
import org.apache.hadoop.conf.Configuration;
import org.junit.Test;

public class TestGangliaSystemMetricsAdaptor{

	@Test
	public void testGangliaSystemMetricsAdaptor() 
			throws IOException, AlreadyRunningException, InterruptedException {
		Configuration conf = new Configuration();
		File baseDir = new File(System.getProperty("test.build.dir", "/tmp"));
		conf.set("chukwaAgent.checkpoint.dir", baseDir.getCanonicalPath());
		conf.setBoolean("chukwaAgent.checkpoint.enabled", false);
		conf.set("chukwaAgent.control.port", "0");
		ChukwaAgent agent = new ChukwaAgent(conf);
		ChunkCatcherConnector chunks = new ChunkCatcherConnector();
		chunks.start();
		
		assertEquals(0, agent.adaptorCount());
		agent.processAddCommand("add org.apache.hadoop.chukwa.contrib." +
				"chukwa_ganglia.GangliaSystemMetricsAdaptor" +
				" GangliaSystemMetrics 10 0");
		assertEquals(1, agent.adaptorCount());
		
		Chunk c = chunks.waitForAChunk();
		assertEquals(InetAddress.getLocalHost().getHostName(), c.getSource());
		assertEquals("GangliaSystemMetrics", c.getDataType());
		assertEquals("Sigar", c.getStreamName());
		
		String report = new String(c.getData());
		System.out.println(report);
		System.out.println(c.getTag("cluster"));	
	}

}

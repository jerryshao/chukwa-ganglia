package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor;

import java.util.List;
import java.util.Map;

import org.apache.hadoop.chukwa.Chunk;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;

public interface GangliaMapperProcessor {
	
	public void process(Chunk chunk, Map<String, 
			List<Put>> putRecord, Map<String, List<Increment>> incrRecord);
}
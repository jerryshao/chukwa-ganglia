package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor;

import java.util.Map;
import java.util.List;

import org.apache.hadoop.chukwa.ChukwaArchiveKey;
import org.apache.hadoop.chukwa.Chunk;
import org.apache.hadoop.chukwa.extraction.demux.processor.mapper.MapProcessor;
import org.apache.hadoop.chukwa.extraction.engine.ChukwaRecord;
import org.apache.hadoop.chukwa.extraction.engine.ChukwaRecordKey;
import org.apache.hadoop.chukwa.util.ExceptionUtil;
import org.apache.hadoop.chukwa.util.RecordConstants;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;
import org.apache.hadoop.mapred.OutputCollector;
import org.apache.hadoop.mapred.Reporter;
import org.apache.log4j.Logger;

public abstract class GangliaAbstractMapperProcessor 
	implements GangliaMapperProcessor, MapProcessor {
	static private Logger log = 
			Logger.getLogger(GangliaAbstractMapperProcessor.class);
	
	protected Chunk currChunk = null;
	
	@Override
	public void process(Chunk chunk, Map<String, List<Put>> putRecord, 
			Map<String, List<Increment>> incrRecord) {
		byte[] ChunkData = chunk.getData();
		int[] OffsetArray = chunk.getRecordOffsets();
		int currPos = 0;
		int start = 0;
		currChunk = chunk;
		
		while (currPos < OffsetArray.length) {
			String str = new String(ChunkData, start, 
					OffsetArray[currPos] - start + 1);
			start = OffsetArray[currPos] + 1;
			currPos++;
			
			String parseStr = RecordConstants.recoverRecordSeparators("\n", str);
			
			try {
				parse(parseStr, putRecord, incrRecord);
			} catch (Throwable t) {
				log.error(ExceptionUtil.getStackTrace(t));
			}
		}
		
	}
	
	abstract protected void parse(String ParseStr, 
			Map<String, List<Put>> putRecord, 
			Map<String, List<Increment>> incrRecord) throws Throwable;
	
	/**
	 * fake implementation, for compatible
	 */
	@Override
	public final void process(ChukwaArchiveKey archiveKey, Chunk chunk,
		      OutputCollector<ChukwaRecordKey, ChukwaRecord> output,
		      Reporter reporter) {
		return;
	}
}
package org.apache.hadoop.chukwa.contrib.chukwa_ganglia.hbasewriter;

import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Timer;
import java.util.TimerTask;

import org.apache.commons.collections.buffer.CircularFifoBuffer;
import org.apache.hadoop.chukwa.Chunk;
import org.apache.hadoop.chukwa.conf.ChukwaConfiguration;
import org.apache.hadoop.chukwa.contrib.chukwa_ganglia.processor.GangliaMapperProcessor;
import org.apache.hadoop.chukwa.datacollection.writer.ChukwaWriter;
import org.apache.hadoop.chukwa.datacollection.writer.PipelineableWriter;
import org.apache.hadoop.chukwa.datacollection.writer.WriterException;
import org.apache.hadoop.chukwa.util.ExceptionUtil;
import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.hbase.HBaseConfiguration;
import org.apache.hadoop.hbase.client.HTableInterface;
import org.apache.hadoop.hbase.client.HTablePool;
import org.apache.hadoop.hbase.client.Increment;
import org.apache.hadoop.hbase.client.Put;
import org.apache.log4j.Logger;

public class GangliaHBaseWriter extends PipelineableWriter {
	private static Logger log = 
			Logger.getLogger(GangliaHBaseWriter.class);
	
	private Timer statTimer = null;
	private Configuration hConf  = null;
	private ChukwaConfiguration cConf = null;
	private HTablePool hPool = null;
	private static Map<String, GangliaMapperProcessor> ProcessorMap =
			new HashMap<String, GangliaMapperProcessor>();
	
	private volatile long DataRxSize = 0;
	
	private class StatTimerTask extends TimerTask {
		private long ts = System.currentTimeMillis();
		private long ds = 0;
		
		public void run() {
			long now = System.currentTimeMillis();			
			long DataRate = (DataRxSize - ds) * 1000 / (now - ts);
			ts = now;
			ds = DataRxSize;
			
			log.info("stat=GangliaHBaseWriter|dataRate=" + DataRate + " B/s");
		}
	};
	
	public GangliaHBaseWriter() {
		statTimer = new Timer();
		hConf = HBaseConfiguration.create();
		cConf = new ChukwaConfiguration();
		hPool = new HTablePool(hConf, 60);
	}

	@Override
	public void close() throws WriterException {
		statTimer.cancel();
		
		try {
			hPool.close();
		} catch (IOException e) {
			throw new WriterException(ExceptionUtil.getStackTrace(e));
		}		
	}

	@Override
	public void init(Configuration arg0) throws WriterException {
		statTimer.schedule(new StatTimerTask(), 1000, 1000 * 10);
	}
	
	@Override
	public CommitStatus add(List<Chunk> chunks) throws WriterException {
		for (Chunk chunk : chunks) {
			String ProcessorName = chunk.getDataType();
			if (!ProcessorName.equals("GangliaSystemMetrics")
					&& !ProcessorName.equals("GangliaJobHistory")) {
				continue;
			}
			
			String ProcessorClass = cConf.get(ProcessorName);
			if (ProcessorClass == null) {
				log.error("class " + ProcessorName + " cannot by found," +
						" please check chukwa-demux-conf.xml");
				continue;
			}
			
			GangliaMapperProcessor processor =
					getMapperProcessor(ProcessorClass);
			if (processor == null) {
				continue;
			}
			
			Map<String, List<Put>> putRecord =
					new HashMap<String, List<Put>>();
			Map<String, List<Increment>> incrRecord = 
					new HashMap<String, List<Increment>>();
			
			synchronized (this) {
				processor.process(chunk, putRecord, incrRecord);
			}
			try {
				for (Map.Entry<String, List<Put>> e : putRecord.entrySet()) {
					HTableInterface table = hPool.getTable(e.getKey());				
					table.put(e.getValue());
					hPool.putTable(table);
				}
			
				for (Map.Entry<String, List<Increment>> e : incrRecord.entrySet()) {
					HTableInterface table = hPool.getTable(e.getKey());					
					for (Increment inc : e.getValue()) {
						table.increment(inc);
					}
					hPool.putTable(table);
				} 
			} catch (IOException t) {
				log.error(ExceptionUtil.getStackTrace(t));
			}
			
			DataRxSize += chunk.getData().length;			
		}
		
		if (next != null) {
			return next.add(chunks);
		}
		
		return ChukwaWriter.COMMIT_OK;
	}
	
	private static synchronized 
	GangliaMapperProcessor getMapperProcessor(String name) {
		GangliaMapperProcessor processor = ProcessorMap.get(name);
		if (processor != null) {
			return processor;
		}
		
		try {
			processor = (GangliaMapperProcessor) Class.forName(name).
					getConstructor().newInstance();
			ProcessorMap.put(name, processor);
		} catch (Exception e) {
			log.error(ExceptionUtil.getStackTrace(e));
			return null;
		}
		
		return processor;
	}

}
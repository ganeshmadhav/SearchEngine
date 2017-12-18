package extractlinkpackage;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.StringTokenizer;

import org.apache.commons.lang3.StringUtils;
import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;
import org.xml.sax.SAXException;


public class WordExtraction {
	public static void main(final String[] args) throws IOException,SAXException, TikaException {
		String url = "/home/ganesh/NYD/NYD/NYD";
		File dir = new File(url);
		ArrayList<String> temp = new ArrayList<String>();
		FileWriter writer=new FileWriter("big.txt");
		for(File file : dir.listFiles()){
		   HtmlParser parser = new HtmlParser();
		   BodyContentHandler handler = new BodyContentHandler(-1);
		   Metadata metadata = new Metadata();
		   FileInputStream inputstream = new FileInputStream(file);
		   ParseContext context = new ParseContext();
		   parser.parse(inputstream, handler, metadata, context);
		   StringTokenizer str = new StringTokenizer(handler.toString());
		   while(str.hasMoreTokens()){
			   String entry = str.nextToken();
			   if(StringUtils.isAlpha(entry) && !entry.equals(""))
				   temp.add(entry);
		   }
	   }
		for(String s : temp)
			 writer.append(s+" ");
		writer.close();
		System.out.println("done");
	}
}
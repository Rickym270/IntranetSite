#!python
"""
  Author:   Ricky Martinez
  Purpose:  callable email class to send emails in a "prettier" format.
  @params:  timestamp:    Timestamp of the email that is being sent
            sender:       Who sent the email
            receiver:     Which email is the email being sent to
            customer_name:Specify who the email is for
            subject:      subject line
            issues:       Body
"""

class twemailer:
  def __init__(self, timestamp, sender, receiver, customer_name, subject, issue):
    self.timestamp = timestamp;
    self.sender = sender;
    self.receiver = receiver;
    self.customer_name = customer_name;
    self.subject = subject;
    self.issue = issue;
#    self.footer = 

  def letter_info(self):
    string_msg = """\
    Message info is as follows:
      Sent on: {}
      From: {}
      To: {}
      For: {}
      Reason: {}\
    """.format(self.timestamp,self.sender,self.receiver,self.customer_name,self.issue)
  
  def build_html(self):
    #Add indentation to first line of email
    self.issue = "&nbsp;&nbsp;&nbsp;&nbsp;" + self.issue;
    # Email style
    style="@font-face{font-family:'Sans';src:url('css/fonts/verdana.ttf')format('truetype');}table{font-family:'Sans';}"
    html = """\
      <html>
        <head>
          <style type="text/css">{}</style>
        </head>
        <body>
          <table style="width:100%;">
          <tr></tr>
            <tr>
              <td colspan="100"><img src='cid:myimage' width="900" /></td>
            </tr>
            <tr>
              <td style="margin-left:10px;margin-top:15px;margin-bottom:10px;">Timestamp: {}</td>
            </tr>
            <tr>
              <td style="margin-left:10px;"><b>From:</b> {}</td>
            </tr>
            <tr>
              <tr>
                <td style="margin-left:10px;margin-bottom:10px;"><b>Issue:</b></td>
              <tr>
              <tr>
                <td style="margin-left:10px;margin-bottom:20px;">{}</td>
              </tr>
            </tr>
          </table>
        </body>
      </html>
    """.format(style, self.timestamp, self.sender, self.issue)
    return html;

  def send_email(self):
    from subprocess import Popen, PIPE
    from email.mime.multipart import MIMEMultipart
    from email.mime.text import MIMEText
    from email.mime.image import MIMEImage

    #Load image and read as collection of bytes
    img_data = open('banner.PNG',"rb").read();

    message = self.build_html();
    print("Message content:\n\n\t\t{}".format(message))

    # Build MIME type, should be related
    html_part = MIMEMultipart(_subtype='related')
    body = MIMEText(message, _subtype="html");
    html_part.attach(body);

    #Create container for image
    img = MIMEImage(img_data, 'png');
    img.add_header("Content-Id","<myimage>")
    img.add_header("Content-Disposition", "inline", filename="myimage")
    html_part.attach(img)

    html_part["Subject"] = self.subject;
    html_part["From"] = self.sender;
    html_part["To"] = self.receiver;
    command = '/usr/sbin/sendmail'
    
    p = Popen([command, '-t', '-i'], stdin=PIPE, stdout=PIPE)
    (stdout , stderr) = p.communicate(html_part.as_string())


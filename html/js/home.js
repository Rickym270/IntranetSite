/*
 * Author:  Ricky Martinez
 * Purpose: PART 1: "Early Schedule/On Call"
 *            This script parses through the excel spreadsheet of early-normal work schedule for the network department.
 *            The way it parses through the information is by getting the current date, and from there, iterating through 
 *            all the rows and columns of the table. To avoid iterating through the whole table (inefficient), the employees
 *            are counted (distance from first row to last row with the first cell, being blank) and then focusing only on the 
 *            current month's section. The index position of the current date in the table is determined by comparing values with
 *            the values for current month,year and date. Once index is found, js iterates through all rows and cells at the indexed position.
 *            Once a matching '2' is found, it saves the persons name (same row, first cell) into an array. These values are then loaded into
 *            their respective HTML elements.
 *            NOTE:   Early days are denoted by a '1' and normal days are denoted by a '2' in the cell.
 *
 *          Part 2: "The Team"
 *            Information is loaded from "employees.js". It displays all information in the DIV element with ID:'theteam'. There are three entries per
 *            ROW div. To do this number of employees are divided by 3 and the ceiling is found to determine number of rows necessary. For each row
 *            a new row and container div are inserted. Since 3 entries, a empty col-*-4 div is inserted 3 times, if there is an employee entry for 
 *            a specified index, the col-*-4 div is populated. Else, it is left empty. last_index is increased by 1 every time to keep track of position.
 *            Once completed, html_string is loaded into 'theteam' div.
 *            NOTE: this should be fully dynamic. Look at employees.js for format on how to add new employees
 *
 */

var month,day,year,month_index_start,month_year;
// getUTCMonth() returns integer. Correlate integer returned with month at the integer returned
var monthNames=["January", "February", "March", "April", "May", "June",
"July", "August", "September", "October", "November", "December"
];

$(document).ready(function(){
  /*
   * Load employees and obtain today's date information
   */
  var html;
  var dateObj = new Date();
  month = dateObj.getUTCMonth() + 1; // months in 1-12 format
  day = dateObj.getUTCDate();
  year = dateObj.getUTCFullYear();

  // Fill in #theteam with employee information
  var rows        = Math.ceil(employees.length /3);
  var last_index = 0;
  var html_string='';
  for( var i =0;i<rows;i++){
    // iterates and inserts necessary rows
    html_string+='<div class="row"><div class="container" style="width:95%">';
    for(var j=0;j<3;j++){
      // NOTE: 3 entries per row are necessary. Insert them
      html_string+='<div class="col-xs-4 col-sm-4 col-md-4 col-lg-4">';
      if(employees[last_index]){
        // populate if employee exists
        html_string+='<div class="card mo-ni-card"><div class="container margin-top margin-left no-bottom-margin"><div id="push2"></div><div class="row v-center"><div class="container"><div class="col-xs-12 col-sm-12 col-md-12 col-lg-12"><div class="card-info padding-left"><h3 id="real_name" class="no-top-margin no-bottom-margin"style="display:block"><b>'+employees[last_index][0]+'</b></h3><p style="display:block">US Networks</p><p style="display:block"><b>Email:</b><p id="real_email">&nbsp;'+employees[last_index][1]+'</p></p></div><!-- card-info padding-left --></div><!-- col-xs-10 col-sm-10 col-md-10 col-lg-9 col-lg-offset-1 --></div><!-- container --></div><!-- row v-center --></div><!-- custom-container margin-top margin-left no-bottom-margin --></div><!-- card mo-ni-card -->'
  
        last_index+=1
      }else{
        last_index+=1
      }
      html_string+='</div>'//col-*-4
    }
    html_string += '</div></div>'//row container
  }
  // Insert into html
  document.getElementById("theteam").innerHTML = html_string;
});

function insertHTML(morning_persons){
  /* 
   * This function inserts HTML into the corresponding HTML elements. 
   * NOTE: Only 2 are specified because there are only 2 Early people daily.
   * NOTE2: IF something changes in the schedule, simply add the following @ line 74
   *        morning_persons=['FNAME LNAME', 'FNAME LNAME']
   */
  var employee_name,employee_email;
  $("#morning_person1_name").empty();
  $("#morning_person2_name").empty();
  $("#morning_person1_name").text(morning_persons[0]);
  $("#morning_person2_name").text(morning_persons[1]);

  $(".personInfo").fadeIn(500);


  $("#morning_person1_email").append(morning_persons[0].replace(/\s+/g,".")+"@tradeweb.com");
  $("#morning_person2_email").append(morning_persons[1].replace(/\s+/g,".")+"@tradeweb.com");
}

function day_check(current_month, date_index){
  /* 
   * Looks at rows and determines who has a '1' value. If so, push name value (td:first) into array
   * Send final array to insertHTML function
   */
  var person;
  var morning_persons = [];
  for( var i =1; i< current_month.length; i++){
    //iterate through this months rows. Starts at one so that row with dates and months is ignored
    var current_person_row = $(current_month[i]);
    var current_person_date = $(current_person_row).find('td').eq(date_index).html();

    if(current_person_date == '1'){
      person=$(current_person_row).find('td:first').html();
      morning_persons.push(person);
    }
  }
  insertHTML(morning_persons);
}
function calcHeader(obj){
  /*
   * This function calculated what index the start of the calendar is. Since January is the first month, it gets the index of the row that includes
   * January <year> this makes sure that the scripts ignores the header of the schedule and goes straight for calendar dates.
   */
  var start_index;
  $(obj).find('td:first').each(function(index){
    if($(this).html() == 'January '+year)start_index = index;
  });
  return start_index;
}
function parseSchedule(obj){
  /*
    Find the frameset and it's child element that correspond to the spreadsheet then load content into the frame and obtain elements within tablebody
  */
  var day_index,active_section;
  var current_monthyear=monthNames[month-1]+ " "+year;
  
  var frame = $(obj).contents().find('frameset>frame[name="frSheet"]')[0];
  var frame_content = frame.contentDocument.body;
  var tbody = $(frame_content).find('table tbody')[0];
  var nextIndex = 0;
  var index = 0;
  var start = calcHeader($(tbody).find('tr'));
  $(tbody).find('tr').slice(start).each(function(i, el){
    // slice determines what row to start iteration. i: index, el:element
    // TODO: Make '11' a dynamic value, not a static
    if(/\s/.test($(this).find('td:first').html()) && $(this).find('td:first').html()!=/&nbsp;/){
      /* increases index until the next non-whitespace character.
         this handles whatever changes are made to the spreadsheet in the case of new-hires.
      */
      index+=1;

      if(i==nextIndex && $(this).find('td:first').html()!= ''){
        month_year = $(this).find('td:first').html();     // get appropriate month and year
        if(month_year == current_monthyear){
          month_index_start = i;    // INFO: remember this all starts at index 11 because dates start 11 rows after row 1
          $(this).find('td').each(function(j, element){
            if($(this).html() == day){
              // get current date index
              day_index = j;
            }
          });
        }
      }
    }else{
      month_index_end = month_index_start+index;
      active_section = $(tbody).find('tr').slice(month_index_start+11,month_index_end+11); //Saves current month schedule in an array
    
      nextIndex += index+1;
      if(!/\s/.test($(this).next('tr').find('td:first').html())){
        //break out if there is no non-whitespace characters
        return false;
      }
      // Reset index value if there is an empty cell. empty cell means end-of-month
      index=0
    }
  });
  day_check(active_section,day_index);
}

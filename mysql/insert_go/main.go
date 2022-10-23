package main

import (
	"database/sql"
	"encoding/json"

	"errors"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"

	_ "github.com/go-sql-driver/mysql"
)

func getRoot(w http.ResponseWriter, r *http.Request) {
	fmt.Printf("got / request\n")
	db, err := sql.Open("mysql", "root:1209-hiRoot@tcp(127.0.0.1:3306)/benchmark")
	defer db.Close()

	if err != nil {
		log.Fatal(err)
	}

	// sql := "INSERT INTO cities(name, population) VALUES ('Moscow', 12506000)"
	sql := "INSERT INTO MyGuests (firstname, lastname, email) VALUES ('John', 'Doe', 'john@example.com')"
	db.Exec(sql)

	data := struct {
		message string
	}{
		message: "Andreah",
	}

	w.Header().Set("Content-Type", "application/json")
	jsonResp, err := json.Marshal(data)
	w.Write(jsonResp)
}
func getHello(w http.ResponseWriter, r *http.Request) {
	fmt.Printf("got /hello request\n")
	io.WriteString(w, "Hello, HTTP!\n")
}

func main() {
	http.HandleFunc("/", getRoot)
	http.HandleFunc("/hello", getHello)

	err := http.ListenAndServe(":3333", nil)
	if errors.Is(err, http.ErrServerClosed) {
		fmt.Printf("server closed\n")
	} else if err != nil {
		fmt.Printf("error starting server: %s\n", err)
		os.Exit(1)
	}
}

// http://localhost:3333
